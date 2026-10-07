package com.example.aquabill
import kotlinx.serialization.Serializable

@Serializable
data class MaintenanceRequest(
    val id: Long? = null,
    val user_code: String,
    val consumer_name: String? = null,
    val address: String? = null,
    val meter_no: String? = null,
    val request_type: String? = null,
    val description: String? = null,
    val status: String? = null,
    val created_at: String? = null
)

// Insert payload: leaves out created_at so the database fills it in.
// Same fields as the web app (Aquabills/user/action/add_request.php), plus the
// consumer's address and meter number so the plumber knows where to go.
@Serializable
data class NewMaintenanceRequest(
    val user_code: String,
    val consumer_name: String,
    val address: String?,
    val meter_no: String?,
    val request_type: String,
    val description: String,
    val status: String
)
