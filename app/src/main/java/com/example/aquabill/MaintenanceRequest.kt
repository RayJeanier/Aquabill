package com.example.aquabill
import kotlinx.serialization.Serializable

@Serializable
data class MaintenanceRequest(
    val user_code: String,
    val request_type: String? = null,
    val description: String? = null,
    val status: String? = null,
    val created_at: String? = null
)

// Insert payload: leaves out created_at so the database fills it in.
// Matches what the web app (Aquabills/user/action/add_request.php) inserts.
@Serializable
data class NewMaintenanceRequest(
    val user_code: String,
    val consumer_name: String,
    val request_type: String,
    val description: String,
    val status: String
)
