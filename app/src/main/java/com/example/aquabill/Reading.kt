package com.example.aquabill
import io.github.jan.supabase.postgrest.from
import io.github.jan.supabase.postgrest.query.Order
import kotlinx.serialization.Serializable

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
    }
}
