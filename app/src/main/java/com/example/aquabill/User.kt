package com.example.aquabill
import kotlinx.serialization.Serializable

@Serializable
data class User(
    val password: String,
    val user_code: String,
    val role: String
)