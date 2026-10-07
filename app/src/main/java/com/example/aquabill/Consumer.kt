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
    companion object {
        suspend fun load(userCode: String): Consumer? = supabase
            .from("consumers")
            .select { filter { eq("user_code", userCode) } }
            .decodeList<Consumer>()
            .firstOrNull()
    }
}
