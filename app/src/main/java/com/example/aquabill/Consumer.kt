package com.example.aquabill
import kotlinx.serialization.Serializable

@Serializable
data class Consumer(
    val user_code: String,
    val name: String? = null
)
