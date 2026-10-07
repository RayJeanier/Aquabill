package com.example.aquabill

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import androidx.core.view.isVisible
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout
import kotlinx.coroutines.launch

// Payment history, same as the web app's Aquabills/user/history.php
class HistoryFragment : Fragment(R.layout.fragment_history) {

    private lateinit var paymentList: LinearLayout
    private lateinit var tvEmpty: TextView
    private lateinit var progress: ProgressBar
    private lateinit var swipeRefresh: SwipeRefreshLayout

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        setupScreenHeader(view, "Payment History")

        paymentList = view.findViewById(R.id.paymentList)
        tvEmpty = view.findViewById(R.id.tvEmpty)
        progress = view.findViewById(R.id.progress)

        view.findViewById<View>(R.id.btnViewBills).setOnClickListener {
            (requireActivity() as MainActivity).openTab(R.id.billsFragment)
        }

        val userCode = arguments?.getString("user_code") ?: return

        swipeRefresh = view.findViewById(R.id.swipeRefresh)
        swipeRefresh.setColorSchemeResources(R.color.aqua_navy)
        swipeRefresh.setOnRefreshListener { loadHistory(userCode) }

        loadHistory(userCode)
    }

    private fun loadHistory(userCode: String) {
        viewLifecycleOwner.lifecycleScope.launch {
            // Pull-to-refresh shows its own spinner
            progress.isVisible = !swipeRefresh.isRefreshing
            try {
                val payments = Payment.load(userCode)
                val totalPaid = payments.sumOf { it.amount ?: 0.0 }
                val bills = Bill.fromHistory(Reading.load(userCode), totalPaid)

                renderTotals(payments.size, totalPaid, bills.sumOf { it.unpaid })
                renderPayments(payments)

            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            } finally {
                progress.isVisible = false
                swipeRefresh.isRefreshing = false
            }
        }
    }

    private fun renderTotals(count: Int, totalPaid: Double, balance: Double) {
        val root = requireView()
        root.findViewById<TextView>(R.id.tvPaymentCount).text = count.toString()
        root.findViewById<TextView>(R.id.tvTotalPaid).text = formatPeso(totalPaid)
        root.findViewById<TextView>(R.id.tvUnpaidBalance).text = formatPeso(balance)
    }

    private fun renderPayments(payments: List<Payment>) {
        paymentList.removeAllViews()
        val inflater = LayoutInflater.from(requireContext())
        payments.forEach { payment ->
            val item = inflater.inflate(R.layout.item_payment, paymentList, false)
            item.findViewById<TextView>(R.id.tvPaymentDate).text =
                formatDate(payment.payment_date, "MMMM dd, yyyy")
            item.findViewById<TextView>(R.id.tvPaymentMethod).text = payment.payment_method ?: "—"
            item.findViewById<TextView>(R.id.tvPaymentAmount).text = formatPeso(payment.amount ?: 0.0)
            paymentList.addView(item)
        }
        tvEmpty.isVisible = payments.isEmpty()
    }
}
