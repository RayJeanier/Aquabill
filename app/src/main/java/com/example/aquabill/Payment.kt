package com.example.aquabill
import io.github.jan.supabase.postgrest.from
import io.github.jan.supabase.postgrest.query.Order
import kotlinx.serialization.Serializable

@Serializable
data class Payment(
    val user_code: String,
    val amount: Double? = null,
    val payment_date: String? = null,
    val payment_method: String? = null
) {
    companion object {
        // A consumer's payments, newest first
        suspend fun load(userCode: String): List<Payment> = supabase
            .from("payments")
            .select {
                filter { eq("user_code", userCode) }
                order("payment_date", Order.DESCENDING)
            }
            .decodeList<Payment>()
    }
}
