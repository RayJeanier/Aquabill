package com.example.aquabill

import android.animation.ValueAnimator
import android.content.Context
import android.graphics.Canvas
import android.graphics.Color
import android.graphics.Paint
import android.graphics.Path
import android.graphics.RectF
import android.os.Build
import android.os.SystemClock
import android.util.AttributeSet
import android.view.View
import kotlin.math.PI
import kotlin.math.cos
import kotlin.math.min
import kotlin.math.sin

// The loading animation from the website's sign-in screen (Aquabills/index.html + css/index.css):
// a drop that fills with moving water, and droplets dripping into rippling water below it.
// Drawn in the web SVG's units: the drop is 120 x 160, the splash 140 x 56 underneath.
class DropLoaderView @JvmOverloads constructor(
    context: Context,
    attrs: AttributeSet? = null
) : View(context, attrs) {

    private val dropPath = Path().apply {
        moveTo(60f, 6f)
        cubicTo(52f, 20f, 18f, 62f, 18f, 100f)
        arcTo(RectF(18f, 58f, 102f, 142f), 180f, -180f, false)
        cubicTo(102f, 62f, 68f, 20f, 60f, 6f)
        close()
    }
    private val shinePath = Path().apply {
        moveTo(38f, 100f)
        cubicTo(38f, 86f, 45f, 71f, 52f, 60f)
    }
    // Three wave periods (120 wide each), so sliding by one period loops seamlessly
    private val waveBack = wavePath(baseline = 12f, amplitude = 12f)
    private val waveFront = wavePath(baseline = 16f, amplitude = 10f)

    private val glassPaint = fill(Color.argb(31, 255, 255, 255))
    private val waveBackPaint = fill(Color.argb(115, 255, 255, 255))
    private val waveFrontPaint = fill(Color.WHITE)
    private val outlinePaint = stroke(4f, Color.WHITE)
    private val shinePaint = stroke(5f, Color.argb(191, 255, 255, 255)).apply { strokeCap = Paint.Cap.ROUND }
    private val dripPaint = fill(Color.WHITE)
    private val ringPaint = stroke(2f, Color.WHITE)
    private val oval = RectF()

    private var startedAt = SystemClock.uptimeMillis()
    private val animate: Boolean
        get() = Build.VERSION.SDK_INT < Build.VERSION_CODES.O || ValueAnimator.areAnimatorsEnabled()

    // Starts the water from empty again
    fun restart() {
        startedAt = SystemClock.uptimeMillis()
        invalidate()
    }

    override fun onDraw(canvas: Canvas) {
        super.onDraw(canvas)
        val t = (SystemClock.uptimeMillis() - startedAt) / 1000f

        // Fit the 140 x 216 drawing into the view, centered
        val scale = min(width / 140f, height / 216f)
        canvas.save()
        canvas.translate((width - 140f * scale) / 2f, (height - 216f * scale) / 2f)
        canvas.scale(scale, scale)

        drawDrop(canvas, t)
        if (animate) drawSplash(canvas, t)

        canvas.restore()
        if (animate && isShown) postInvalidateOnAnimation()
    }

    private fun drawDrop(canvas: Canvas, t: Float) {
        // Bobs up and down 6 units every 2.4 s
        val bob = if (animate) -3f * (1f - cos(2f * PI.toFloat() * t / 2.4f)) else 0f

        canvas.save()
        canvas.translate(10f, bob)
        canvas.drawPath(dropPath, glassPaint)

        // Water level: starts empty (144) and fills toward the top (40), then drains and refills
        val level = if (animate) 92f + 52f * cos(t * 1.1f) else 70f
        val shift = if (animate) (t * 75f) % 120f else 0f

        canvas.save()
        canvas.clipPath(dropPath)
        canvas.translate(0f, level)
        canvas.save()
        canvas.translate(shift - 120f, 0f)
        canvas.drawPath(waveBack, waveBackPaint)
        canvas.restore()
        canvas.save()
        canvas.translate(-shift, 0f)
        canvas.drawPath(waveFront, waveFrontPaint)
        canvas.restore()
        canvas.restore()

        canvas.drawPath(dropPath, outlinePaint)
        canvas.drawPath(shinePath, shinePaint)
        canvas.restore()
    }

    // Two drips falling 0.8 s apart, each landing in rings that spread out
    private fun drawSplash(canvas: Canvas, t: Float) {
        canvas.save()
        canvas.translate(0f, 162f)

        for (delay in floatArrayOf(0f, 0.8f)) {
            val p = phase(t - delay, 1.6f) ?: continue
            val (y, size, alpha) = when {
                p < 0.7f -> {
                    val fall = p / 0.7f
                    Triple(-8f + 42f * fall * fall, 0.6f + 0.4f * fall, if (p < 0.15f) p / 0.15f else 1f)
                }
                p < 0.8f -> {
                    val land = (p - 0.7f) / 0.1f
                    Triple(34f + 4f * land, 1f - 0.6f * land, 1f - land)
                }
                else -> continue
            }
            dripPaint.alpha = (255 * alpha).toInt()
            oval.set(70f - 4.5f * size, y, 70f + 4.5f * size, y + 13f * size)
            canvas.drawOval(oval, dripPaint)
        }

        for (delay in floatArrayOf(0.55f, 0.95f, 1.35f)) {
            val p = phase(t - delay, 1.6f) ?: continue
            val grow = 1f - (1f - p) * (1f - p) // ease out
            val s = 0.15f + 0.85f * grow
            ringPaint.alpha = (255 * 0.8f * 0.9f * (1f - p)).toInt()
            oval.set(70f - 40f * s, 41f - 9f * s, 70f + 40f * s, 41f + 9f * s)
            canvas.drawOval(oval, ringPaint)
        }
        canvas.restore()
    }

    // 0..1 position within a repeating cycle, or null before it first starts
    private fun phase(t: Float, period: Float): Float? = if (t < 0) null else (t % period) / period

    override fun onVisibilityChanged(changedView: View, visibility: Int) {
        super.onVisibilityChanged(changedView, visibility)
        if (visibility == VISIBLE) invalidate()
    }

    private fun wavePath(baseline: Float, amplitude: Float) = Path().apply {
        moveTo(0f, baseline)
        for (i in 0 until 6) {
            val x = i * 60f
            val controlY = if (i % 2 == 0) baseline - amplitude else baseline + amplitude
            quadTo(x + 30f, controlY, x + 60f, baseline)
        }
        lineTo(360f, 200f)
        lineTo(0f, 200f)
        close()
    }

    private fun fill(color: Int) = Paint(Paint.ANTI_ALIAS_FLAG).apply {
        style = Paint.Style.FILL
        this.color = color
    }

    private fun stroke(width: Float, color: Int) = Paint(Paint.ANTI_ALIAS_FLAG).apply {
        style = Paint.Style.STROKE
        strokeWidth = width
        strokeJoin = Paint.Join.ROUND
        this.color = color
    }
}
