package com.example.aquabill
import io.github.jan.supabase.postgrest.from
import io.github.jan.supabase.postgrest.query.Order
import kotlinx.serialization.Serializable
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import java.util.TimeZone

@Serializable
data class Reading(
    val id: Long? = null,
    val user_code: String,
    val previous_reading: Double? = null,
    val current_reading: Double? = null,
    val amount: Double? = null,
    val reading_date: String? = null
) {
    val usage: Double
        get() = ((current_reading ?: 0.0) - (previous_reading ?: 0.0)).coerceAtLeast(0.0)

    // reading_date is stored in UTC (the web inserts NOW())
    val isThisMonth: Boolean
        get() = reading_date?.take(7) == utcNow("yyyy-MM")

    companion object {
        // A consumer's meter readings, oldest first
        suspend fun load(userCode: String): List<Reading> = supabase
            .from("readings")
            .select {
                filter { eq("user_code", userCode) }
                order("reading_date", Order.ASCENDING)
                order("id", Order.ASCENDING)
            }
            .decodeList<Reading>()

        // Every consumer's latest reading, keyed by user_code
        suspend fun loadLatest(): Map<String, Reading> = supabase
            .from("readings")
            .select { order("id", Order.DESCENDING) }
            .decodeList<Reading>()
            .distinctBy { it.user_code }
            .associateBy { it.user_code }

        fun utcNow(pattern: String): String =
            SimpleDateFormat(pattern, Locale.US)
                .apply { timeZone = TimeZone.getTimeZone("UTC") }
                .format(Date())
    }
}

// Same columns the web's action/add_reading.php fills in
@Serializable
data class NewReading(
    val user_code: String,
    val consumer_name: String?,
    val previous_reading: Double,
    val current_reading: Double,
    val total_usage: Int,
    val amount: Double,
    val reading_date: String
)
