package com.example.aquabill

import android.graphics.RectF
import com.google.mlkit.vision.text.Text
import java.util.Locale

// Picks the meter reading out of the text ML Kit found in a camera frame.
// Only text inside the scan box counts (when given). The odometer digits are the biggest
// numbers on the dial, so taller text ranks first; numbers below the previous reading rank last.
object MeterOcr {

    private val NUMBER = Regex("""\d[\d ]*(?:[.,]\d+)?""")
    private val LETTER_O_NEXT_TO_DIGIT = Regex("""(?<=\d)[Oo]|[Oo](?=\d)""")

    fun candidates(text: Text, previous: Double?, meterNo: String?, region: RectF? = null): List<Double> {
        val found = mutableListOf<Pair<Double, Int>>() // value to text height

        for (block in text.textBlocks) for (line in block.lines) {
            val box = line.boundingBox ?: continue
            if (region != null && !region.contains(box.exactCenterX(), box.exactCenterY())) continue

            // Odometer wheels often come back as "0 0 4 5 2" or with the letter O for 0
            val cleaned = line.text.replace(LETTER_O_NEXT_TO_DIGIT, "0")

            for (match in NUMBER.findAll(cleaned)) {
                val pieces = listOf(match.value.replace(" ", "")) + match.value.split(" ")
                for (piece in pieces.distinct()) {
                    val digits = piece.replace(',', '.')
                    // Readings have at least 3 wheels; skip the meter's own serial number
                    if (digits.count { it.isDigit() } !in 3..8) continue
                    if (meterNo != null && digits.trimStart('0') == meterNo.trimStart('0')) continue
                    digits.toDoubleOrNull()?.let { found += it to box.height() }
                }
            }
        }

        return found
            .sortedWith(
                compareByDescending<Pair<Double, Int>> { previous == null || it.first >= previous }
                    .thenByDescending { it.second }
            )
            .map { it.first }
            .distinct()
            .take(5)
    }
}

// 452.0 -> "452", 452.5 -> "452.5" (for putting a number back in a text field)
fun plainNumber(value: Double): String =
    String.format(Locale.US, "%.2f", value).trimEnd('0').trimEnd('.')
