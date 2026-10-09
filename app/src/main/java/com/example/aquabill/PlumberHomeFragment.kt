package com.example.aquabill

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.widget.ImageView
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import androidx.core.content.ContextCompat
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.view.isVisible
import androidx.core.view.updatePadding
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout
import com.google.android.material.button.MaterialButton
import com.google.android.material.chip.ChipGroup
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import io.github.jan.supabase.postgrest.from
import io.github.jan.supabase.postgrest.query.Order
import kotlinx.coroutines.launch

// Plumber home: every maintenance request, with the same status controls as the
// web admin's Aquabills/maintenance.php
class PlumberHomeFragment : Fragment(R.layout.fragment_plumber_home) {

    private var requests: List<MaintenanceRequest> = emptyList()

    private lateinit var chipFilter: ChipGroup
    private lateinit var requestList: LinearLayout
    private lateinit var tvEmpty: TextView
    private lateinit var progress: ProgressBar
    private lateinit var swipeRefresh: SwipeRefreshLayout

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val name = arguments?.getString("name") ?: arguments?.getString("user_code").orEmpty()
        view.findViewById<TextView>(R.id.tvGreeting).text = "${greetingForNow()},"
        view.findViewById<TextView>(R.id.tvName).text = name
        view.findViewById<View>(R.id.btnLogout).setOnClickListener { confirmLogout() }

        chipFilter = view.findViewById(R.id.chipFilter)
        requestList = view.findViewById(R.id.requestList)
        tvEmpty = view.findViewById(R.id.tvEmpty)
        progress = view.findViewById(R.id.progress)
        swipeRefresh = view.findViewById(R.id.swipeRefresh)

        chipFilter.setOnCheckedStateChangeListener { _, _ -> renderList() }

        swipeRefresh.setColorSchemeResources(R.color.aqua_navy)
        swipeRefresh.setOnRefreshListener { loadRequests() }

        applyWindowInsets(view)
        loadRequests()
    }

    private fun loadRequests() {
        viewLifecycleOwner.lifecycleScope.launch {
            // Pull-to-refresh shows its own spinner
            progress.isVisible = !swipeRefresh.isRefreshing
            try {
                requests = supabase
                    .from("maintenance_requests")
                    .select { order("created_at", Order.DESCENDING) }
                    .decodeList<MaintenanceRequest>()
                renderCounts()
                renderList()

            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            } finally {
                progress.isVisible = false
                swipeRefresh.isRefreshing = false
            }
        }
    }

    private fun renderCounts() {
        val root = requireView()
        fun count(status: String) = requests.count { (it.status ?: "Open") == status }

        root.showCount(R.id.countOpen, "Open", count("Open"), R.drawable.ic_inbox, R.color.danger_red, R.color.danger_red_bg)
        root.showCount(R.id.countInProgress, "In Progress", count("In Progress"), R.drawable.ic_schedule,
            R.color.warning_orange, R.color.warning_orange_bg)
        root.showCount(R.id.countDone, "Done", count("Resolved"), R.drawable.ic_check_circle,
            R.color.success_green, R.color.success_green_bg)
        root.showCount(R.id.countTotal, "Total", requests.size, R.drawable.ic_list_all, R.color.aqua_navy, R.color.tile_pay_bg)
    }

    // Fills one layout_status_count card
    private fun View.showCount(cardId: Int, label: String, count: Int, icon: Int, color: Int, background: Int) {
        val card = findViewById<View>(cardId)
        card.findViewById<TextView>(R.id.countValue).text = count.toString()
        card.findViewById<TextView>(R.id.countLabel).text = label
        card.findViewById<View>(R.id.countIconTile).backgroundTintList =
            ContextCompat.getColorStateList(context, background)
        card.findViewById<ImageView>(R.id.countIcon).apply {
            setImageResource(icon)
            imageTintList = ContextCompat.getColorStateList(context, color)
        }
    }

    private fun selectedStatus(): String? = when (chipFilter.checkedChipId) {
        R.id.chipOpen -> "Open"
        R.id.chipInProgress -> "In Progress"
        R.id.chipDone -> "Resolved"
        else -> null // All
    }

    private fun renderList() {
        val filter = selectedStatus()
        val shown = if (filter == null) requests else requests.filter { it.status == filter }

        requestList.removeAllViews()
        val inflater = LayoutInflater.from(requireContext())
        shown.forEach { request ->
            val item = inflater.inflate(R.layout.item_plumber_request, requestList, false)
            item.findViewById<TextView>(R.id.tvRequestType).text = request.request_type ?: "Request"
            item.findViewById<TextView>(R.id.tvConsumer).text =
                listOfNotNull(request.consumer_name, request.user_code).joinToString(" · ")
            // Requests sent before address/meter were saved won't have them
            val location = listOfNotNull(
                request.address?.takeIf { it.isNotBlank() },
                request.meter_no?.takeIf { it.isNotBlank() }?.let { "Meter no. $it" }
            )
            item.findViewById<View>(R.id.locationRow).isVisible = location.isNotEmpty()
            item.findViewById<TextView>(R.id.tvLocation).text = location.joinToString(" · ")

            item.findViewById<TextView>(R.id.tvRequestDescription).text = request.description.orEmpty()
            item.findViewById<TextView>(R.id.tvRequestDate).text =
                formatDate(request.created_at, "MMMM dd, yyyy")
            item.findViewById<MaterialButton>(R.id.btnStatus).apply {
                showStatus(request.status)
                setOnClickListener { pickStatus(request) }
            }
            requestList.addView(item)
        }
        tvEmpty.isVisible = shown.isEmpty()
    }

    // The status dropdown: the current status, in its color
    private fun MaterialButton.showStatus(status: String?) {
        val (color, background) = requestStatusColors(status)
        text = requestStatusLabel(status)
        setTextColor(ContextCompat.getColor(context, color))
        iconTint = ContextCompat.getColorStateList(context, color)
        backgroundTintList = ContextCompat.getColorStateList(context, background)
    }

    private fun pickStatus(request: MaintenanceRequest) {
        val current = REQUEST_STATUSES.indexOf(request.status ?: "Open")
        MaterialAlertDialogBuilder(requireContext())
            .setTitle("Set status")
            .setSingleChoiceItems(REQUEST_STATUSES.map(::requestStatusLabel).toTypedArray(), current) { dialog, which ->
                dialog.dismiss()
                val newStatus = REQUEST_STATUSES[which]
                if (newStatus != request.status) updateStatus(request, newStatus)
            }
            .setNegativeButton("Cancel", null)
            .show()
    }

    private fun updateStatus(request: MaintenanceRequest, newStatus: String) {
        val id = request.id ?: return
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                // A row blocked by row-level security is silently skipped, so ask for the
                // updated row back and treat "nothing returned" as a failure
                val updated = supabase
                    .from("maintenance_requests")
                    .update({ set("status", newStatus) }) {
                        select()
                        filter { eq("id", id) }
                    }
                    .decodeList<MaintenanceRequest>()

                if (updated.isEmpty()) {
                    Toast.makeText(requireContext(), "Couldn't update: not allowed by Supabase", Toast.LENGTH_LONG).show()
                    return@launch
                }
                Toast.makeText(requireContext(), "Marked as ${requestStatusLabel(newStatus)}", Toast.LENGTH_SHORT).show()
                loadRequests()

            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }

    // Keep the header and refresh spinner clear of the status bar
    private fun applyWindowInsets(view: View) {
        val header = view.findViewById<View>(R.id.header)
        val headerTop = header.paddingTop
        val spinnerDistance = (64 * resources.displayMetrics.density).toInt()

        ViewCompat.setOnApplyWindowInsetsListener(view) { _, insets ->
            val top = insets.getInsets(WindowInsetsCompat.Type.systemBars()).top
            header.updatePadding(top = headerTop + top)
            swipeRefresh.setProgressViewOffset(false, top, top + spinnerDistance)
            insets
        }
    }
}
