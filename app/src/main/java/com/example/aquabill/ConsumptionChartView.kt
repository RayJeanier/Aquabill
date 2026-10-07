package com.example.aquabill

import android.content.Context
import android.graphics.Canvas
import android.graphics.LinearGradient
import android.graphics.Paint
import android.graphics.RectF
import android.graphics.Shader
import android.util.AttributeSet
import android.view.View
import androidx.core.content.ContextCompat

// Simple bar chart for monthly water usage; the last bar (current month) is highlighted.
class ConsumptionChartView @JvmOverloads constructor(
    context: Context,
    attrs: AttributeSet? = null
) : View(context, attrs) {

    private var labels: List<String> = emptyList()
    private var values: List<Float> = emptyList()

    private val density = resources.displayMetrics.density
    private val navy = ContextCompat.getColor(context, R.color.aqua_navy)
    private val blue = ContextCompat.getColor(context, R.color.aqua_blue)

    private val barPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
        color = ContextCompat.getColor(context, R.color.tile_bills_bg)
    }
    private val highlightPaint = Paint(Paint.ANTI_ALIAS_FLAG)
    private val labelPaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
        color = ContextCompat.getColor(context, R.color.text_secondary)
        textSize = 11 * resources.displayMetrics.scaledDensity
        textAlign = Paint.Align.CENTER
    }
    private val valuePaint = Paint(Paint.ANTI_ALIAS_FLAG).apply {
        color = ContextCompat.getColor(context, R.color.text_primary)
        textSize = 11 * resources.displayMetrics.scaledDensity
        textAlign = Paint.Align.CENTER
        isFakeBoldText = true
    }
    private val rect = RectF()

    fun setData(labels: List<String>, values: List<Float>) {
        this.labels = labels
        this.values = values
        invalidate()
    }

    override fun onDraw(canvas: Canvas) {
        super.onDraw(canvas)
        if (values.isEmpty()) return

        val max = values.max().coerceAtLeast(1f)
        val labelArea = 22 * density
        val valueArea = 18 * density
        val chartTop = paddingTop + valueArea
        val chartBottom = height - paddingBottom - labelArea
        val slot = (width - paddingLeft - paddingRight).toFloat() / values.size
        val barWidth = slot * 0.5f
        val radius = 8 * density

        values.forEachIndexed { i, value ->
            val cx = paddingLeft + slot * i + slot / 2
            val top = chartBottom - (chartBottom - chartTop) * (value / max)
            rect.set(cx - barWidth / 2, top, cx + barWidth / 2, chartBottom)

            val isCurrent = i == values.lastIndex
            if (isCurrent) {
                highlightPaint.shader = LinearGradient(
                    0f, rect.top, 0f, rect.bottom, blue, navy, Shader.TileMode.CLAMP
                )
                canvas.drawText(formatValue(value), cx, top - 6 * density, valuePaint)
            }
            canvas.drawRoundRect(rect, radius, radius, if (isCurrent) highlightPaint else barPaint)

            labels.getOrNull(i)?.let {
                canvas.drawText(it, cx, height - paddingBottom - 4 * density, labelPaint)
            }
        }
    }

    private fun formatValue(value: Float) =
        if (value % 1f == 0f) value.toInt().toString() else "%.1f".format(value)
}
