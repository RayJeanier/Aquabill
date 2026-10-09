package com.example.aquabill

import android.graphics.RectF
import android.os.Bundle
import android.util.Log
import android.util.Size
import android.view.View
import android.widget.ImageView
import android.widget.TextView
import android.widget.Toast
import androidx.annotation.OptIn
import androidx.camera.core.Camera
import androidx.camera.core.CameraSelector
import androidx.camera.core.ExperimentalGetImage
import androidx.camera.core.ImageAnalysis
import androidx.camera.core.ImageProxy
import androidx.camera.core.Preview
import androidx.camera.core.TorchState
import androidx.camera.core.resolutionselector.AspectRatioStrategy
import androidx.camera.core.resolutionselector.ResolutionSelector
import androidx.camera.core.resolutionselector.ResolutionStrategy
import androidx.camera.lifecycle.ProcessCameraProvider
import androidx.camera.view.PreviewView
import androidx.core.content.ContextCompat
import androidx.core.os.bundleOf
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.view.isVisible
import androidx.core.view.updatePadding
import androidx.fragment.app.Fragment
import androidx.fragment.app.setFragmentResult
import androidx.navigation.fragment.findNavController
import com.google.mlkit.vision.common.InputImage
import com.google.mlkit.vision.text.TextRecognition
import com.google.mlkit.vision.text.latin.TextRecognizerOptions
import java.util.concurrent.ExecutorService
import java.util.concurrent.Executors
import kotlin.math.max

// Live camera for reading a water meter: the number inside the box is shown as it's
// recognized, and tapping the shutter sends it back to MeterReadingFragment.
class MeterScanFragment : Fragment(R.layout.fragment_meter_scan) {

    private lateinit var previewView: PreviewView
    private lateinit var scanBox: View
    private lateinit var tvLiveReading: TextView
    private lateinit var btnTorch: ImageView

    private lateinit var analysisExecutor: ExecutorService
    private val textRecognizer = TextRecognition.getClient(TextRecognizerOptions.DEFAULT_OPTIONS)
    private var camera: Camera? = null

    private var previous: Double? = null
    private var meterNo: String? = null

    // Best number in the box from the latest camera frame
    private var liveReading: Double? = null

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        previous = arguments?.getDouble(ARG_PREVIOUS, -1.0)?.takeIf { it >= 0 }
        meterNo = arguments?.getString(ARG_METER_NO)

        previewView = view.findViewById(R.id.previewView)
        scanBox = view.findViewById(R.id.scanBox)
        tvLiveReading = view.findViewById(R.id.tvLiveReading)
        btnTorch = view.findViewById(R.id.btnTorch)

        view.findViewById<View>(R.id.btnClose).setOnClickListener { findNavController().navigateUp() }
        view.findViewById<View>(R.id.btnCapture).setOnClickListener { capture() }
        btnTorch.setOnClickListener { toggleTorch() }

        // Keep the top bar and shutter clear of the status and gesture bars
        val topBar = view.findViewById<View>(R.id.topBar)
        val bottomBar = view.findViewById<View>(R.id.bottomBar)
        val topPadding = topBar.paddingTop
        val bottomPadding = bottomBar.paddingBottom
        ViewCompat.setOnApplyWindowInsetsListener(view) { _, insets ->
            val bars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            topBar.updatePadding(top = topPadding + bars.top)
            bottomBar.updatePadding(bottom = bottomPadding + bars.bottom)
            insets
        }

        analysisExecutor = Executors.newSingleThreadExecutor()
        showLiveReading(null)
        startCamera()
    }

    override fun onDestroyView() {
        super.onDestroyView()
        analysisExecutor.shutdown()
    }

    override fun onDestroy() {
        super.onDestroy()
        textRecognizer.close()
    }

    private fun startCamera() {
        val providerFuture = ProcessCameraProvider.getInstance(requireContext())
        providerFuture.addListener({
            val view = view ?: return@addListener
            val provider = providerFuture.get()

            // Same 4:3 shape for the preview and the analyzed frames, so the box lines up
            val fourByThree = AspectRatioStrategy.RATIO_4_3_FALLBACK_AUTO_STRATEGY
            val preview = Preview.Builder()
                .setResolutionSelector(ResolutionSelector.Builder().setAspectRatioStrategy(fourByThree).build())
                .build()
                .also { it.surfaceProvider = previewView.surfaceProvider }

            val analysis = ImageAnalysis.Builder()
                .setResolutionSelector(
                    ResolutionSelector.Builder()
                        .setAspectRatioStrategy(fourByThree)
                        .setResolutionStrategy(
                            ResolutionStrategy(Size(1280, 960), ResolutionStrategy.FALLBACK_RULE_CLOSEST_HIGHER_THEN_LOWER)
                        )
                        .build()
                )
                .setBackpressureStrategy(ImageAnalysis.STRATEGY_KEEP_ONLY_LATEST)
                .build()
                .also { it.setAnalyzer(analysisExecutor, ::readFrame) }

            val selector = if (provider.hasCamera(CameraSelector.DEFAULT_BACK_CAMERA)) {
                CameraSelector.DEFAULT_BACK_CAMERA
            } else {
                CameraSelector.DEFAULT_FRONT_CAMERA
            }

            try {
                provider.unbindAll()
                camera = provider.bindToLifecycle(viewLifecycleOwner, selector, preview, analysis)
                btnTorch.isVisible = camera?.cameraInfo?.hasFlashUnit() == true
            } catch (e: Exception) {
                Log.e("MeterScan", "Couldn't start the camera", e)
                Toast.makeText(requireContext(), "Couldn't start the camera", Toast.LENGTH_LONG).show()
                view.post { findNavController().navigateUp() }
            }
        }, ContextCompat.getMainExecutor(requireContext()))
    }

    // Runs on every camera frame (one at a time; frames arriving meanwhile are dropped)
    @OptIn(ExperimentalGetImage::class)
    private fun readFrame(frame: ImageProxy) {
        val mediaImage = frame.image ?: run { frame.close(); return }
        val rotation = frame.imageInfo.rotationDegrees
        val upright = rotation % 180 == 0
        val width = if (upright) frame.width else frame.height
        val height = if (upright) frame.height else frame.width

        textRecognizer.process(InputImage.fromMediaImage(mediaImage, rotation))
            .addOnSuccessListener { text ->
                if (view == null) return@addOnSuccessListener
                showLiveReading(MeterOcr.candidates(text, previous, meterNo, scanBoxInFrame(width, height)).firstOrNull())
            }
            .addOnCompleteListener { frame.close() }
    }

    // Where the on-screen box falls in the camera frame. The preview fills the screen
    // (center-cropped), so undo that scaling and cropping.
    private fun scanBoxInFrame(frameWidth: Int, frameHeight: Int): RectF? {
        val viewWidth = previewView.width.toFloat()
        val viewHeight = previewView.height.toFloat()
        if (viewWidth == 0f || viewHeight == 0f) return null

        // The box sits inside a centered container, so measure it against the preview itself
        val previewAt = IntArray(2).also(previewView::getLocationInWindow)
        val boxAt = IntArray(2).also(scanBox::getLocationInWindow)
        val boxLeft = (boxAt[0] - previewAt[0]).toFloat()
        val boxTop = (boxAt[1] - previewAt[1]).toFloat()

        val scale = max(viewWidth / frameWidth, viewHeight / frameHeight)
        val cropX = (frameWidth * scale - viewWidth) / 2
        val cropY = (frameHeight * scale - viewHeight) / 2
        val region = RectF(
            (boxLeft + cropX) / scale,
            (boxTop + cropY) / scale,
            (boxLeft + scanBox.width + cropX) / scale,
            (boxTop + scanBox.height + cropY) / scale
        )
        // A little slack so a number poking out of the box (shaky hands) still counts
        region.inset(-region.width() * 0.1f, -region.height() * 0.25f)
        return region
    }

    private fun showLiveReading(value: Double?) {
        liveReading = value
        tvLiveReading.text = value?.let(::formatCubicMeters) ?: "Looking for numbers…"
        tvLiveReading.alpha = if (value == null) 0.7f else 1f
    }

    private fun capture() {
        val value = liveReading
        if (value == null) {
            Toast.makeText(requireContext(), "No numbers in the box yet. Move closer and hold steady.", Toast.LENGTH_SHORT).show()
            return
        }
        setFragmentResult(RESULT_KEY, bundleOf(RESULT_READING to value))
        findNavController().navigateUp()
    }

    private fun toggleTorch() {
        val camera = camera ?: return
        val on = camera.cameraInfo.torchState.value != TorchState.ON
        camera.cameraControl.enableTorch(on)
        btnTorch.alpha = if (on) 1f else 0.6f
    }

    companion object {
        const val ARG_PREVIOUS = "previous"
        const val ARG_METER_NO = "meter_no"
        const val RESULT_KEY = "meter_scan"
        const val RESULT_READING = "reading"
    }
}
