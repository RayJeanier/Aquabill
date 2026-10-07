package com.example.aquabill
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
}
