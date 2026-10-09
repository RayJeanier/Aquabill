package com.example.aquabill

import android.animation.ValueAnimator
import android.content.Context
import android.graphics.Color
import android.graphics.LinearGradient
import android.graphics.Matrix
import android.graphics.Shader
import android.util.AttributeSet
import android.view.animation.LinearInterpolator
import androidx.appcompat.widget.AppCompatTextView
import androidx.core.animation.doOnRepeat

// White text with a slow light sweep across it, like "simplified." on the website's login
// (.shine in Aquabills/css/index.css): sweeps for 2.7 s, rests, repeats every 4.5 s.
class ShineTextView @JvmOverloads constructor(
    context: Context,
    attrs: AttributeSet? = null
) : AppCompatTextView(context, attrs) {

    private val shaderMatrix = Matrix()
    private var shader: LinearGradient? = null
    private var animator: ValueAnimator? = null

    override fun onSizeChanged(w: Int, h: Int, oldw: Int, oldh: Int) {
        super.onSizeChanged(w, h, oldw, oldh)
        // Gradient 2.5x the text width, light band in the middle
        shader = LinearGradient(
            0f, 0f, 2.5f * w, 0f,
            intArrayOf(Color.WHITE, Color.WHITE, Color.parseColor("#B9F3FF"), Color.WHITE, Color.WHITE),
            floatArrayOf(0f, 0.35f, 0.5f, 0.65f, 1f),
            Shader.TileMode.CLAMP
        )
        moveShine(1f)
    }

    // position 1 = light band left of the text, 0 = right of it
    private fun moveShine(position: Float) {
        val shader = shader ?: return
        shaderMatrix.setTranslate(-1.5f * width * position, 0f)
        shader.setLocalMatrix(shaderMatrix)
        paint.shader = shader
        invalidate()
    }

    override fun onAttachedToWindow() {
        super.onAttachedToWindow()
        animator = ValueAnimator.ofFloat(0f, 1f).apply {
            duration = 4500
            startDelay = 1400
            repeatCount = ValueAnimator.INFINITE
            interpolator = LinearInterpolator()
            addUpdateListener {
                val f = it.animatedFraction
                // ease-in-out over the first 60%, then hold
                val sweep = if (f < 0.6f) f / 0.6f else 1f
                val eased = sweep * sweep * (3f - 2f * sweep)
                moveShine(1f - eased)
            }
            doOnRepeat { moveShine(1f) }
            start()
        }
    }

    override fun onDetachedFromWindow() {
        animator?.cancel()
        animator = null
        super.onDetachedFromWindow()
    }
}
