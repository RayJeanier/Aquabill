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
import com.google.android.material.tabs.TabLayout
import kotlinx.coroutines.launch

class BillsFragment : Fragment(R.layout.fragment_bills) {

    private var bills: List<Bill> = emptyList()

    private lateinit var tabs: TabLayout
    private lateinit var billList: LinearLayout
    private lateinit var tvEmpty: TextView
    private lateinit var progress: ProgressBar
    private lateinit var swipeRefresh: SwipeRefreshLayout

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        setupScreenHeader(view, "My Bills")

        tabs = view.findViewById(R.id.tabs)
        billList = view.findViewById(R.id.billList)
        tvEmpty = view.findViewById(R.id.tvEmpty)
        progress = view.findViewById(R.id.progress)

        tabs.addOnTabSelectedListener(object : TabLayout.OnTabSelectedListener {
            override fun onTabSelected(tab: TabLayout.Tab) = renderList()
            override fun onTabUnselected(tab: TabLayout.Tab) {}
            override fun onTabReselected(tab: TabLayout.Tab) {}
        })

        val userCode = arguments?.getString("user_code") ?: return

        swipeRefresh = view.findViewById(R.id.swipeRefresh)
        swipeRefresh.setColorSchemeResources(R.color.aqua_navy)
        swipeRefresh.setOnRefreshListener { loadBills(userCode) }

        loadBills(userCode)
    }

    private fun loadBills(userCode: String) {
        viewLifecycleOwner.lifecycleScope.launch {
            // Pull-to-refresh shows its own spinner
            progress.isVisible = !swipeRefresh.isRefreshing
            try {
                bills = Bill.load(userCode)
                renderSummary()
                renderList()

            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            } finally {
                progress.isVisible = false
                swipeRefresh.isRefreshing = false
            }
        }
    }

    private fun renderSummary() {
        val unpaid = bills.filterNot { it.isPaid }
        val root = requireView()
        root.findViewById<TextView>(R.id.tvOutstanding).text = formatPeso(unpaid.sumOf { it.unpaid })
        root.findViewById<TextView>(R.id.tvUnpaidCount).text = when (unpaid.size) {
            0 -> "You're all paid up"
            1 -> "1 unpaid bill"
            else -> "${unpaid.size} unpaid bills"
        }
    }

    private fun renderList() {
        val showPaid = tabs.selectedTabPosition == 1
        val shown = bills.filter { it.isPaid == showPaid }.reversed() // newest first

        billList.removeAllViews()
        val inflater = LayoutInflater.from(requireContext())
        shown.forEach { bill ->
            val item = inflater.inflate(R.layout.item_bill, billList, false)
            item.findViewById<TextView>(R.id.tvBillMonth).text = formatDate(bill.readOn, "MMMM yyyy")

            // Unpaid bills show their due date; partially paid ones also show what's left
            val details = mutableListOf(formatCubicMeters(bill.reading.usage))
            details += if (bill.isPaid) "Read ${formatDate(bill.readOn, "MMM d")}"
                       else "Due ${formatDate(bill.dueOn, "MMM d")}"
            if (bill.status == "Partially paid") details += "${formatPeso(bill.unpaid)} left"
            item.findViewById<TextView>(R.id.tvBillDetails).text = details.joinToString(" · ")

            item.findViewById<TextView>(R.id.tvBillAmount).text = formatPeso(bill.amount)
            item.findViewById<TextView>(R.id.tvBillStatus).showBillStatus(bill)
            billList.addView(item)
        }

        tvEmpty.text = if (showPaid) "No paid bills yet" else "No unpaid bills"
        tvEmpty.isVisible = shown.isEmpty()
    }
}
