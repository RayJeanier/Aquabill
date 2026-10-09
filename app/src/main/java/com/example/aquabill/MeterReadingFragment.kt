package com.example.aquabill

import android.Manifest
import android.content.pm.PackageManager
import android.os.Bundle
import android.util.Log
import android.view.LayoutInflater
import android.view.View
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import androidx.activity.result.contract.ActivityResultContracts
import androidx.core.content.ContextCompat
import androidx.core.os.bundleOf
import androidx.core.view.ViewCompat
import androidx.core.view.WindowCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.view.isVisible
import androidx.core.view.updatePadding
import androidx.core.widget.NestedScrollView
import androidx.core.widget.doAfterTextChanged
import androidx.fragment.app.Fragment
import androidx.fragment.app.setFragmentResultListener
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import com.google.android.material.button.MaterialButton
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.google.android.material.textfield.TextInputEditText
import com.google.android.material.textfield.TextInputLayout
import io.github.jan.supabase.postgrest.from
import kotlinx.coroutines.launch
import kotlin.math.roundToInt

// One consumer's meter: type the reading or scan it with the camera, and it's saved as a
// bill the same way the web's action/add_reading.php does it. Every save adds a new reading,
// and its current reading becomes the previous reading for the next one.
class MeterReadingFragment : Fragment(R.layout.fragment_meter_reading) {

    private lateinit var userCode: String
    private var consumer: Consumer? = null
    private var readings: List<Reading> = emptyList()

    // The latest saved reading, which the new one is measured from; null for a consumer's first reading
    private var previous: Double? = null

    // Number read by MeterScanFragment, filled in once the page has (re)loaded
    private var pendingScan: Double? = null

    private lateinit var scroll: NestedScrollView
    private lateinit var progress: ProgressBar
    private lateinit var tilPrevious: TextInputLayout
    private lateinit var etPrevious: TextInputEditText
    private lateinit var tilCurrent: TextInputLayout
    private lateinit var etCurrent: TextInputEditText
    private lateinit var tvUsagePreview: TextView
    private lateinit var tvAmountPreview: TextView
    private lateinit var btnScanMeter: MaterialButton
    private lateinit var btnSave: MaterialButton
    private lateinit var readingList: LinearLayout

    private val requestCamera = registerForActivityResult(ActivityResultContracts.RequestPermission()) { granted ->
        if (granted) openScanner()
        else Toast.makeText(requireContext(), "Camera permission is needed to scan the meter", Toast.LENGTH_LONG).show()
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setFragmentResultListener(MeterScanFragment.RESULT_KEY) { _, result ->
            pendingScan = result.getDouble(MeterScanFragment.RESULT_READING)
        }
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        userCode = requireArguments().getString("user_code").orEmpty()
        setupScreenHeader(view, "Meter Reading")

        scroll = view.findViewById(R.id.scroll)
        progress = view.findViewById(R.id.progress)
        tilPrevious = view.findViewById(R.id.tilPrevious)
        etPrevious = view.findViewById(R.id.etPrevious)
        tilCurrent = view.findViewById(R.id.tilCurrent)
        etCurrent = view.findViewById(R.id.etCurrent)
        tvUsagePreview = view.findViewById(R.id.tvUsagePreview)
        tvAmountPreview = view.findViewById(R.id.tvAmountPreview)
        btnScanMeter = view.findViewById(R.id.btnScanMeter)
        btnSave = view.findViewById(R.id.btnSaveReading)
        readingList = view.findViewById(R.id.readingList)

        etPrevious.doAfterTextChanged { tilPrevious.error = null; updatePreview() }
        etCurrent.doAfterTextChanged { updatePreview() }
        btnScanMeter.setOnClickListener { scanMeter() }
        btnSave.setOnClickListener { saveTyped() }

        // Keep the form above the gesture bar and the keyboard
        val scrollBottom = scroll.paddingBottom
        ViewCompat.setOnApplyWindowInsetsListener(scroll) { v, insets ->
            val bottom = maxOf(
                insets.getInsets(WindowInsetsCompat.Type.navigationBars()).bottom,
                insets.getInsets(WindowInsetsCompat.Type.ime()).bottom
            )
            v.updatePadding(bottom = scrollBottom + bottom)
            insets
        }

        loadReadings()
    }

    private fun loadReadings() {
        viewLifecycleOwner.lifecycleScope.launch {
            progress.isVisible = true
            try {
                consumer = Consumer.load(userCode)
                if (consumer == null) {
                    Toast.makeText(requireContext(), "Consumer $userCode not found", Toast.LENGTH_LONG).show()
                    findNavController().navigateUp()
                    return@launch
                }
                readings = Reading.load(userCode)
                render()
                scroll.isVisible = true

                pendingScan?.let { scanned ->
                    pendingScan = null
                    confirmScanned(scanned)
                }

            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            } finally {
                progress.isVisible = false
            }
        }
    }

    private fun render() {
        val root = requireView()
        val consumer = consumer ?: return

        root.findViewById<TextView>(R.id.tvConsumerName).text = consumer.name ?: consumer.user_code
        root.findViewById<TextView>(R.id.tvUserCode).text = consumer.user_code
        root.findViewById<TextView>(R.id.tvLocation).text = consumer.locationText()

        // The last current reading is where the next one starts
        val latest = readings.lastOrNull()
        previous = latest?.current_reading

        root.findViewById<TextView>(R.id.tvFormTitle).text = "New reading"
        root.findViewById<TextView>(R.id.tvFormNote).text = if (latest != null) {
            "Last read on ${formatDate(latest.reading_date, "MMM dd, yyyy")}. " +
                "Type the number on the meter, or tap Scan to read it with the camera."
        } else {
            "Type the number on the meter, or tap Scan to read it with the camera."
        }
        etCurrent.text = null
        btnSave.text = "Save reading"

        // A consumer's first reading needs a starting number typed in
        root.findViewById<View>(R.id.previousBox).isVisible = previous != null
        root.findViewById<TextView>(R.id.tvPrevious).text = previous?.let(::formatCubicMeters).orEmpty()
        tilPrevious.isVisible = previous == null

        renderHistory()
        updatePreview()
    }

    private fun renderHistory() {
        readingList.removeAllViews()
        val inflater = LayoutInflater.from(requireContext())
        readings.asReversed().forEach { reading ->
            val item = inflater.inflate(R.layout.item_reading, readingList, false)
            // Older readings were saved before amounts were stored
            val amount = reading.amount?.takeIf { it > 0 } ?: Pricing.computeBill(reading.usage)

            item.findViewById<TextView>(R.id.tvReadingDate).text = formatDate(reading.reading_date, "MMMM dd, yyyy")
            item.findViewById<TextView>(R.id.tvReadingRange).text =
                "${plainNumber(reading.previous_reading ?: 0.0)} → ${formatCubicMeters(reading.current_reading ?: 0.0)}"
            item.findViewById<TextView>(R.id.tvReadingUsage).text = formatCubicMeters(reading.usage)
            item.findViewById<TextView>(R.id.tvReadingAmount).text = formatPeso(amount)
            readingList.addView(item)
        }
        requireView().findViewById<View>(R.id.tvNoReadings).isVisible = readings.isEmpty()
    }

    private fun previousValue(): Double? = previous ?: etPrevious.text?.toString()?.toDoubleOrNull()

    private fun currentValue(): Double? = etCurrent.text?.toString()?.toDoubleOrNull()

    // Usage and bill update as the reading is typed or scanned
    private fun updatePreview() {
        val prev = previousValue()
        val current = currentValue()

        if (prev == null || current == null || current < prev) {
            tvUsagePreview.text = "—"
            tvAmountPreview.text = "—"
            tilCurrent.error = if (prev != null && current != null) "Lower than the previous reading" else null
            return
        }
        tilCurrent.error = null
        val usage = current - prev
        tvUsagePreview.text = formatCubicMeters(usage)
        tvAmountPreview.text = formatPeso(Pricing.computeBill(usage))
    }

    private fun saveTyped() {
        val prev = previousValue() ?: run {
            tilPrevious.error = "Enter the starting reading"
            return
        }
        val current = currentValue() ?: run {
            tilCurrent.error = "Enter the meter reading"
            return
        }
        if (current < prev) {
            tilCurrent.error = "Lower than the previous reading"
            return
        }
        saveReading(prev, current)
    }

    private fun saveReading(prev: Double, current: Double) {
        hideKeyboard()
        val usage = current - prev
        val totalUsage = usage.roundToInt()
        val amount = (Pricing.computeBill(usage) * 100).roundToInt() / 100.0

        viewLifecycleOwner.lifecycleScope.launch {
            btnSave.isEnabled = false
            try {
                supabase.from("readings").insert(
                    NewReading(
                        user_code = userCode,
                        consumer_name = consumer?.name,
                        previous_reading = prev,
                        current_reading = current,
                        total_usage = totalUsage,
                        amount = amount,
                        reading_date = Reading.utcNow("yyyy-MM-dd'T'HH:mm:ss")
                    )
                )

                Toast.makeText(
                    requireContext(),
                    "Reading saved: ${formatCubicMeters(usage)} used · ${formatPeso(amount)}",
                    Toast.LENGTH_LONG
                ).show()
                loadReadings()

            } catch (e: Exception) {
                Log.e("MeterReading", "Saving reading failed", e)
                MaterialAlertDialogBuilder(requireContext())
                    .setTitle("Couldn't save the reading")
                    .setMessage(e.message ?: e.toString())
                    .setPositiveButton("OK", null)
                    .show()
            } finally {
                btnSave.isEnabled = true
            }
        }
    }

    // --- Scanning the meter with the camera ---

    private fun scanMeter() {
        val granted = ContextCompat.checkSelfPermission(requireContext(), Manifest.permission.CAMERA) ==
            PackageManager.PERMISSION_GRANTED
        if (granted) openScanner() else requestCamera.launch(Manifest.permission.CAMERA)
    }

    private fun openScanner() {
        findNavController().navigate(
            R.id.action_meterReadingFragment_to_meterScanFragment,
            bundleOf(
                MeterScanFragment.ARG_PREVIOUS to (previousValue() ?: -1.0),
                MeterScanFragment.ARG_METER_NO to consumer?.meter_no
            )
        )
    }

    // Puts the scanned number in the form and offers to save it straight away
    private fun confirmScanned(value: Double) {
        etCurrent.setText(plainNumber(value))

        val prev = previousValue()
        if (prev == null) {
            tilPrevious.error = "Enter the starting reading, then tap Save"
            etPrevious.requestFocus()
            return
        }
        if (value < prev) {
            Toast.makeText(requireContext(), "Scanned ${formatCubicMeters(value)} is lower than the previous reading", Toast.LENGTH_LONG).show()
            return
        }

        val usage = value - prev
        MaterialAlertDialogBuilder(requireContext())
            .setTitle("Save this reading?")
            .setMessage(
                "Meter: ${formatCubicMeters(value)}\n" +
                    "Previous: ${formatCubicMeters(prev)}\n" +
                    "Usage: ${formatCubicMeters(usage)}\n" +
                    "Amount: ${formatPeso(Pricing.computeBill(usage))}"
            )
            .setPositiveButton("Save") { _, _ -> saveReading(prev, value) }
            .setNegativeButton("Edit") { _, _ -> etCurrent.requestFocus() }
            .setNeutralButton("Retake") { _, _ -> scanMeter() }
            .show()
    }

    private fun hideKeyboard() {
        val view = view ?: return
        WindowCompat.getInsetsController(requireActivity().window, view).hide(WindowInsetsCompat.Type.ime())
    }
}
