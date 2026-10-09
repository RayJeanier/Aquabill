package com.example.aquabill
import kotlinx.serialization.Serializable

// A logged-in account, as returned by the app_login() database function (never the password)
@Serializable
data class User(
    val user_code: String,
    val role: String,
    val username: String? = null
)
