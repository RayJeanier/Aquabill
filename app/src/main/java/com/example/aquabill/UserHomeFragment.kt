package com.example.aquabill

import android.os.Bundle
import android.view.View
import android.view.ViewGroup
import android.widget.TextView
import android.widget.Toast
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.view.isVisible
import androidx.core.view.updateLayoutParams
import androidx.core.view.updatePadding
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import androidx.navigation.navOptions
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import kotlinx.coroutines.launch
import java.util.Calendar

class UserHomeFragment : Fragment(R.layout.fragment_user_home) {

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val name = arguments?.getString("name") ?: arguments?.getString("user_code").orEmpty()
        view.findViewById<TextView>(R.id.tvGreeting).text = "${greetingForNow()},"
        view.findViewById<TextView>(R.id.tvName).text = name

        applyWindowInsets(view)

        val swipeRefresh = view.findViewById<SwipeRefreshLayout>(R.id.swipeRefresh)
        swipeRefresh.setColorSchemeResources(R.color.aqua_navy)
        arguments?.getString("user_code")?.let { userCode ->
            swipeRefresh.setOnRefreshListener { loadBills(view, userCode) }
            loadBills(view, userCode)
        }

        view.findViewById<View>(R.id.btnLogout).setOnClickListener { confirmLogout() }

        val userArgs = Bundle().apply { putString("user_code", arguments?.getString("user_code")) }

        view.findViewById<View>(R.id.actionBills).setOnClickListener {
            findNavController().navigate(R.id.action_userHomeFragment_to_billsFragment, userArgs)
        }
        view.findViewById<View>(R.id.actionRepair).setOnClickListener {
            findNavController().navigate(R.id.action_userHomeFragment_to_maintenanceFragment, userArgs)
        }

        val comingSoon = View.OnClickListener {
            Toast.makeText(requireContext(), "Coming soon", Toast.LENGTH_SHORT).show()
        }
        listOf(R.id.btnPayNow, R.id.actionPay)
            .forEach { view.findViewById<View>(it).setOnClickListener(comingSoon) }
    }

    private fun loadBills(view: View, userCode: String) {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val bills = Bill.load(userCode)
                showAmountDue(view, bills)
                showConsumption(view, bills.takeLast(6))
            } catch (e: Exception) {
                // Keep whatever is already on screen; just report the failure
                Toast.makeText(requireContext(), "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            } finally {
                view.findViewById<SwipeRefreshLayout>(R.id.swipeRefresh).isRefreshing = false
            }
        }
    }

    // Amount due = what's still owed on every bill (earlier unpaid + latest).
    // Usage / reading / due date describe the latest reading.
    private fun showAmountDue(view: View, bills: List<Bill>) {
        val period = view.findViewById<TextView>(R.id.tvBillPeriod)
        val badge = view.findViewById<TextView>(R.id.tvBillBadge)
        val amount = view.findViewById<TextView>(R.id.tvAmount)
        val cents = view.findViewById<TextView>(R.id.tvAmountCents)
        val earlierLine = view.findViewById<TextView>(R.id.tvPastDue)
        val usage = view.findViewById<TextView>(R.id.tvUsage)
        val readingDate = view.findViewById<TextView>(R.id.tvReadingDate)
        val dueDate = view.findViewById<TextView>(R.id.tvDueDate)
        val payNow = view.findViewById<View>(R.id.btnPayNow)

        val latest = bills.lastOrNull()
        if (latest == null) {
            period.text = "No bills yet"
            badge.isVisible = false
            amount.text = "₱0"
            cents.text = ".00"
            earlierLine.isVisible = false
            usage.text = "—"
            readingDate.text = "—"
            dueDate.text = "—"
            payNow.isVisible = false
            return
        }

        val unpaid = bills.filterNot { it.isPaid }
        val earlier = unpaid.filter { it !== latest }
        val balance = unpaid.sumOf { it.unpaid }

        period.text = formatDate(latest.readOn, "MMMM yyyy")

        // "₱1,284.50" -> "₱1,284" + ".50"
        val total = formatPeso(balance)
        amount.text = total.substringBefore('.')
        cents.text = "." + total.substringAfter('.')

        earlierLine.isVisible = earlier.isNotEmpty()
        val billWord = if (earlier.size == 1) "bill" else "bills"
        earlierLine.text = "Includes ${formatPeso(earlier.sumOf { it.unpaid })} from ${earlier.size} earlier $billWord"

        usage.text = formatCubicMeters(latest.reading.usage)
        readingDate.text = formatDate(latest.readOn, "MMM d")
        dueDate.text = formatDate(latest.dueOn, "MMM d")

        // Badge follows the oldest bill still owed (the most urgent), or "Paid" when nothing is owed
        val oldestUnpaid = unpaid.firstOrNull()
        badge.isVisible = true
        if (oldestUnpaid != null) badge.showBillStatus(oldestUnpaid, oldestUnpaid.dueLabel)
        else badge.showBillStatus(latest, "Paid")

        payNow.isVisible = unpaid.isNotEmpty()
    }

    private fun showConsumption(view: View, recentBills: List<Bill>) {
        val chart = view.findViewById<ConsumptionChartView>(R.id.consumptionChart)
        chart.setData(
            recentBills.map { formatDate(it.reading.reading_date, "MMM") },
            recentBills.map { it.reading.usage.toFloat() }
        )
        chart.isVisible = recentBills.isNotEmpty()
        view.findViewById<View>(R.id.tvChartEmpty).isVisible = recentBills.isEmpty()
    }

    private fun confirmLogout() {
        MaterialAlertDialogBuilder(requireContext())
            .setTitle("Log out")
            .setMessage("Are you sure you want to log out?")
            .setNegativeButton("Cancel", null)
            .setPositiveButton("Log out") { _, _ ->
                // Clear the back stack so Back can't return to the home screen
                findNavController().navigate(R.id.logInFragment, null, navOptions {
                    popUpTo(R.id.nav_graph) { inclusive = true }
                })
            }
            .show()
    }

    private fun greetingForNow(): String {
        val hour = Calendar.getInstance().get(Calendar.HOUR_OF_DAY)
        return when {
            hour < 12 -> "Good morning"
            hour < 18 -> "Good afternoon"
            else -> "Good evening"
        }
    }

    // Keep the header clear of the status bar
    private fun applyWindowInsets(view: View) {
        val header = view.findViewById<View>(R.id.header)
        val content = view.findViewById<View>(R.id.content)
        val headerTop = header.paddingTop
        val contentTop = (content.layoutParams as ViewGroup.MarginLayoutParams).topMargin

        val swipeRefresh = view.findViewById<SwipeRefreshLayout>(R.id.swipeRefresh)
        val spinnerDistance = (64 * resources.displayMetrics.density).toInt()

        ViewCompat.setOnApplyWindowInsetsListener(view) { _, insets ->
            val top = insets.getInsets(WindowInsetsCompat.Type.systemBars()).top
            header.updatePadding(top = headerTop + top)
            swipeRefresh.setProgressViewOffset(false, top, top + spinnerDistance)
            content.updateLayoutParams<ViewGroup.MarginLayoutParams> {
                topMargin = contentTop + top
            }
            insets
        }
    }
}
