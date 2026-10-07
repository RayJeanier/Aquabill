package com.example.aquabill

import android.view.View
import android.widget.TextView
import androidx.core.content.ContextCompat
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.view.updatePadding
import androidx.fragment.app.Fragment
import androidx.navigation.fragment.findNavController
import androidx.navigation.navOptions
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import java.text.SimpleDateFormat
import java.util.Calendar
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

// Maintenance request statuses, same as the web admin (Aquabills/maintenance.php)
val REQUEST_STATUSES = listOf("Open", "In Progress", "Resolved")

// Same colors as the web: Open red, In Progress orange, Resolved green
fun TextView.showRequestStatus(status: String?) {
    val label = status ?: "Open"
    when (label) {
        "Open" -> showStatusBadge(label, R.color.danger_red, R.color.danger_red_bg)
        "In Progress" -> showStatusBadge(label, R.color.warning_orange, R.color.warning_orange_bg)
        "Resolved" -> showStatusBadge(label, R.color.success_green, R.color.success_green_bg)
        else -> showStatusBadge(label, R.color.text_secondary, R.color.divider)
    }
}

fun greetingForNow(): String {
    val hour = Calendar.getInstance().get(Calendar.HOUR_OF_DAY)
    return when {
        hour < 12 -> "Good morning"
        hour < 18 -> "Good afternoon"
        else -> "Good evening"
    }
}

fun Fragment.confirmLogout() {
    MaterialAlertDialogBuilder(requireContext())
        .setTitle("Log out")
        .setMessage("Are you sure you want to log out?")
        .setNegativeButton("Cancel", null)
        .setPositiveButton("Log out") { _, _ ->
            // Clear the back stack so Back can't return to a logged-in screen
            findNavController().navigate(R.id.logInFragment, null, navOptions {
                popUpTo(R.id.nav_graph) { inclusive = true }
            })
        }
        .show()
}

fun TextView.showStatusBadge(text: String, textColorRes: Int, backgroundColorRes: Int) {
    this.text = text
    setTextColor(ContextCompat.getColor(context, textColorRes))
    backgroundTintList = ContextCompat.getColorStateList(context, backgroundColorRes)
}
