package com.example.aquabill
import kotlinx.serialization.Serializable

@Serializable
data class Payment(
    val user_code: String,
    val amount: Double? = null,
    val payment_date: String? = null,
    val payment_method: String? = null
)
