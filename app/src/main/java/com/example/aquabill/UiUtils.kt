package com.example.aquabill

import android.view.View
import android.widget.TextView
import androidx.core.content.ContextCompat
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.view.updatePadding
import androidx.fragment.app.Fragment
import androidx.navigation.fragment.findNavController
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale

fun formatPeso(amount: Double): String = String.format(Locale.US, "₱%,.2f", amount)

// 12.50 -> "12.5 m³", 300.00 -> "300 m³" (same as the web's format_number)
fun formatCubicMeters(value: Double): String =
    String.format(Locale.US, "%,.2f", value).trimEnd('0').trimEnd('.') + " m³"

// Accepts "2026-05-24" or a timestamp like "2026-05-24T08:00:00+00:00"; returns local midnight of that day
fun parseDay(raw: String?): Date? {
    if (raw == null || raw.length < 10) return null
    return try {
        SimpleDateFormat("yyyy-MM-dd", Locale.US).parse(raw.substring(0, 10))
    } catch (e: Exception) {
        null
    }
}

fun formatDate(date: Date?, pattern: String): String =
    date?.let { SimpleDateFormat(pattern, Locale.US).format(it) } ?: "—"

fun formatDate(raw: String?, pattern: String): String = formatDate(parseDay(raw), pattern)

// Pill colors per bill status, same as the web: Paid green, Overdue red, otherwise orange
fun TextView.showBillStatus(bill: Bill, text: String = bill.status) = when (bill.status) {
    "Paid" -> showStatusBadge(text, R.color.success_green, R.color.success_green_bg)
    "Overdue" -> showStatusBadge(text, R.color.danger_red, R.color.danger_red_bg)
    else -> showStatusBadge(text, R.color.warning_orange, R.color.warning_orange_bg)
}

// Header with back button shared by the consumer sub-screens (layout_screen_header.xml)
fun Fragment.setupScreenHeader(root: View, title: String) {
    root.findViewById<TextView>(R.id.tvScreenTitle).text = title
    root.findViewById<View>(R.id.btnBack).setOnClickListener { findNavController().navigateUp() }

    val header = root.findViewById<View>(R.id.screenHeader)
    val baseTop = header.paddingTop
    ViewCompat.setOnApplyWindowInsetsListener(header) { v, insets ->
        v.updatePadding(top = baseTop + insets.getInsets(WindowInsetsCompat.Type.statusBars()).top)
        insets
    }
}

fun TextView.showStatusBadge(text: String, textColorRes: Int, backgroundColorRes: Int) {
    this.text = text
    setTextColor(ContextCompat.getColor(context, textColorRes))
    backgroundTintList = ContextCompat.getColorStateList(context, backgroundColorRes)
}
