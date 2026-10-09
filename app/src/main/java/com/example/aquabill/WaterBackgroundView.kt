package com.example.aquabill

import android.animation.ValueAnimator
import android.content.Context
import android.graphics.Canvas
import android.graphics.Color
import android.graphics.Paint
import android.graphics.Path
import android.os.Build
import android.os.SystemClock
import android.util.AttributeSet
import android.view.View

// The moving decoration behind the website's login page (Aquabills/css/index.css):
// bubbles rising up the screen and three layers of waves drifting along the bottom.
class WaterBackgroundView @JvmOverloads constructor(
    context: Context,
    attrs: AttributeSet? = null
) : View(context, attrs) {

    private class Wave(val path: Path, val alpha: Float, val heightFraction: Float, val seconds: Float, val reverse: Boolean)

    private class Bubble(val left: Float, val sizeDp: Float, val seconds: Float, val delay: Float)

    // Each wave is two periods of the web SVG (viewBox 1440 x 120), drawn twice the screen width
    private val waves = listOf(
        Wave(wavePath(60f, 20f), 0.08f, 1f, 22f, reverse = false),
        Wave(wavePath(70f, 35f), 0.12f, 0.85f, 15f, reverse = true),
        Wave(wavePath(80f, 55f), 0.16f, 0.7f, 10f, reverse = false)
    )

    private val bubbles = listOf(
        Bubble(0.08f, 14f, 11f, 0f), Bubble(0.22f, 8f, 9f, 2f),
        Bubble(0.35f, 18f, 14f, 4f), Bubble(0.50f, 10f, 10f, 1f),
        Bubble(0.63f, 6f, 8f, 5f), Bubble(0.74f, 16f, 13f, 3f),
        Bubble(0.86f, 9f, 9.5f, 6f), Bubble(0.94f, 12f, 12f, 7.5f)
    )

    private val density = resources.displayMetrics.density
    private val wavesHeight = 140f * density
    private val startedAt = SystemClock.uptimeMillis()

    private val wavePaint = Paint(Paint.ANTI_ALIAS_FLAG).apply { color = Color.WHITE }
    private val bubbleFill = Paint(Paint.ANTI_ALIAS_FLAG).apply { color = Color.argb(46, 255, 255, 255) }
    private val bubbleStroke = Paint(Paint.ANTI_ALIAS_FLAG).apply {
        style = Paint.Style.STROKE
        strokeWidth = density
        color = Color.argb(77, 255, 255, 255)
    }

    private val animate: Boolean
        get() = Build.VERSION.SDK_INT < Build.VERSION_CODES.O || ValueAnimator.areAnimatorsEnabled()

    override fun onDraw(canvas: Canvas) {
        super.onDraw(canvas)
        val t = (SystemClock.uptimeMillis() - startedAt) / 1000f

        if (animate) drawBubbles(canvas, t)
        drawWaves(canvas, if (animate) t else 0f)

        if (animate && isShown) postInvalidateOnAnimation()
    }

    private fun drawWaves(canvas: Canvas, t: Float) {
        val w = width.toFloat()
        for (wave in waves) {
            val layerHeight = wavesHeight * wave.heightFraction
            // Slides left by one screen width (half the wave), then loops
            val progress = (t / wave.seconds) % 1f
            val offset = -w * (if (wave.reverse) 1f - progress else progress)

            wavePaint.alpha = (255 * wave.alpha).toInt()
            canvas.save()
            canvas.translate(offset, height - layerHeight)
            canvas.scale(2f * w / 1440f, layerHeight / 120f)
            canvas.drawPath(wave.path, wavePaint)
            canvas.restore()
        }
    }

    private fun drawBubbles(canvas: Canvas, t: Float) {
        val h = height.toFloat()
        for (b in bubbles) {
            val local = t - b.delay
            if (local < 0) continue
            val p = (local / b.seconds) % 1f

            // Rises past the top while drifting right then left, fading in and out
            val rise = 1.05f * h * p
            val drift = if (p < 0.5f) 12f * (p / 0.5f) else 12f - 20f * ((p - 0.5f) / 0.5f)
            val alpha = when {
                p < 0.1f -> p / 0.1f
                p < 0.9f -> 1f - 0.4f * (p - 0.1f) / 0.8f
                else -> 0.6f * (1f - p) / 0.1f
            }
            val radius = b.sizeDp * density / 2f
            val cx = width * b.left + radius + drift * density
            val cy = h + 30f * density - radius - rise

            bubbleFill.alpha = (46 * alpha).toInt()
            bubbleStroke.alpha = (77 * alpha).toInt()
            canvas.drawCircle(cx, cy, radius, bubbleFill)
            canvas.drawCircle(cx, cy, radius, bubbleStroke)
        }
    }

    override fun onVisibilityChanged(changedView: View, visibility: Int) {
        super.onVisibilityChanged(changedView, visibility)
        if (visibility == VISIBLE) invalidate()
    }

    // "M0 y Q180 y-a 360 y T720 y T1080 y T1440 y V120 H0 Z" from the web SVG
    private fun wavePath(baseline: Float, crest: Float) = Path().apply {
        val amplitude = baseline - crest
        moveTo(0f, baseline)
        for (i in 0 until 4) {
            val x = i * 360f
            val controlY = if (i % 2 == 0) baseline - amplitude else baseline + amplitude
            quadTo(x + 180f, controlY, x + 360f, baseline)
        }
        lineTo(1440f, 120f)
        lineTo(0f, 120f)
        close()
    }
}
