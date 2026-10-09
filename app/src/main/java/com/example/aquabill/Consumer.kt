package com.example.aquabill
import io.github.jan.supabase.postgrest.from
import kotlinx.serialization.Serializable

@Serializable
data class Consumer(
    val user_code: String,
    val name: String? = null,
    val address: String? = null,
    val meter_no: String? = null
) {
    // "San Vicente · Meter no. 11111"
    fun locationText(): String = listOf(
        address?.takeIf { it.isNotBlank() } ?: "No address on file",
        "Meter no. ${meter_no?.takeIf { it.isNotBlank() } ?: "—"}"
    ).joinToString(" · ")

    companion object {
        suspend fun load(userCode: String): Consumer? = supabase
            .from("consumers")
            .select { filter { eq("user_code", userCode) } }
            .decodeList<Consumer>()
            .firstOrNull()
    }
}
