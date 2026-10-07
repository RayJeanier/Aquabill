package com.example.aquabill

import java.util.Calendar
import java.util.Date
import java.util.concurrent.TimeUnit
import kotlin.math.max
import kotlin.math.min
import kotlin.math.roundToInt

// Water rates. Must match the web app's Aquabills/config/pricing.json (edited from its pricing page).
object Pricing {
    const val MINIMUM_CHARGE = 120.0 // covers the first MINIMUM_CUBIC m³
    const val MINIMUM_CUBIC = 10.0
    const val EXCESS_RATE = 8.0      // per m³ above MINIMUM_CUBIC
    const val DUE_DAYS = 15          // a bill is due this many days after its reading

    fun computeBill(usage: Double) = MINIMUM_CHARGE + max(0.0, usage - MINIMUM_CUBIC) * EXCESS_RATE
}

// One meter reading = one bill. Mirrors the web app's Aquabills/user/includes/account.php.
data class Bill(
    val reading: Reading,
    val amount: Double,
    val paid: Double,
    val unpaid: Double,
    val readOn: Date?,
    val dueOn: Date?
) {
    val isPaid: Boolean get() = unpaid <= 0.0

    // Days until due; negative once overdue
    val daysLeft: Int?
        get() = dueOn?.let {
            TimeUnit.MILLISECONDS.toDays(it.time - startOfToday().time + TimeUnit.HOURS.toMillis(12)).toInt()
        }

    val status: String
        get() = when {
            isPaid -> "Paid"
            (daysLeft ?: 0) < 0 -> "Overdue"
            paid > 0 -> "Partially paid"
            else -> "Unpaid"
        }

    // "Paid" / "Overdue by 3 days" / "Due today" / "Due in 6 days"
    val dueLabel: String
        get() {
            val days = daysLeft
            return when {
                isPaid -> "Paid"
                days == null -> status
                days < 0 -> "Overdue by ${-days} day${if (days == -1) "" else "s"}"
                days == 0 -> "Due today"
                else -> "Due in $days day${if (days == 1) "" else "s"}"
            }
        }

    companion object {
        // Loads a consumer's bills (oldest first) from the readings and payments tables
        suspend fun load(userCode: String): List<Bill> =
            fromHistory(Reading.load(userCode), Payment.load(userCode).sumOf { it.amount ?: 0.0 })

        // Every payment is applied to the oldest bill first
        fun fromHistory(readingsOldestFirst: List<Reading>, totalPaid: Double): List<Bill> {
            var remaining = totalPaid
            return readingsOldestFirst.map { reading ->
                // Older readings were saved before amounts were stored
                val stored = reading.amount ?: 0.0
                val amount = if (stored > 0) stored else Pricing.computeBill(reading.usage)

                val paid = min(remaining, amount).roundCents()
                remaining = (remaining - paid).roundCents()

                val readOn = parseDay(reading.reading_date)
                val dueOn = readOn?.let {
                    Calendar.getInstance().apply {
                        time = it
                        add(Calendar.DAY_OF_YEAR, Pricing.DUE_DAYS)
                    }.time
                }
                Bill(reading, amount, paid, (amount - paid).roundCents(), readOn, dueOn)
            }
        }

        private fun Double.roundCents() = (this * 100).roundToInt() / 100.0

        private fun startOfToday(): Date = Calendar.getInstance().apply {
            set(Calendar.HOUR_OF_DAY, 0)
            set(Calendar.MINUTE, 0)
            set(Calendar.SECOND, 0)
            set(Calendar.MILLISECOND, 0)
        }.time
    }
}
