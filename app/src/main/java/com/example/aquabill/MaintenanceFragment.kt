package com.example.aquabill

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.widget.ArrayAdapter
import android.widget.Button
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import androidx.core.view.isVisible
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout
import com.google.android.material.textfield.MaterialAutoCompleteTextView
import com.google.android.material.textfield.TextInputEditText
import io.github.jan.supabase.postgrest.from
import io.github.jan.supabase.postgrest.query.Order
import kotlinx.coroutines.launch

class MaintenanceFragment : Fragment(R.layout.fragment_maintenance) {

    // Keep in sync with the web app (Aquabills/user/service.php)
    private val requestTypes = listOf(
        "Leak Report",
        "Meter Issue",
        "No Water",
        "Low Water Pressure",
        "Water Quality",
        "Other"
    )

    private lateinit var userCode: String
    private lateinit var requestList: LinearLayout
    private lateinit var tvEmpty: TextView
    private lateinit var progress: ProgressBar
    private lateinit var swipeRefresh: SwipeRefreshLayout

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)
        setupScreenHeader(view, "Repair")

        requestList = view.findViewById(R.id.requestList)
        tvEmpty = view.findViewById(R.id.tvEmpty)
        progress = view.findViewById(R.id.progress)

        val etRequestType = view.findViewById<MaterialAutoCompleteTextView>(R.id.etRequestType)
        val etDescription = view.findViewById<TextInputEditText>(R.id.etDescription)
        val btnSubmit = view.findViewById<Button>(R.id.btnSubmitRequest)

        etRequestType.setAdapter(
            ArrayAdapter(requireContext(), android.R.layout.simple_list_item_1, requestTypes)
        )

        userCode = arguments?.getString("user_code") ?: return

        btnSubmit.setOnClickListener {
            val type = etRequestType.text.toString().trim()
            val description = etDescription.text.toString().trim()

            if (type !in requestTypes || description.isEmpty()) {
                Toast.makeText(requireContext(), "Fill all fields", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }
            if (description.length > 1000) {
                Toast.makeText(requireContext(), "Description is too long (max 1000 characters)", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }

            btnSubmit.isEnabled = false
            viewLifecycleOwner.lifecycleScope.launch {
                try {
                    // Fetched fresh so the request carries the consumer's current details
                    val consumer = Consumer.load(userCode)
                    val consumerName = consumer?.name ?: throw IllegalStateException("Account not found")
                    showServiceLocation(consumer)

                    supabase.from("maintenance_requests").insert(
                        NewMaintenanceRequest(
                            user_code = userCode,
                            consumer_name = consumerName,
                            address = consumer.address,
                            meter_no = consumer.meter_no,
                            request_type = type,
                            description = description,
                            status = "Open"
                        )
                    )
                    Toast.makeText(requireContext(), "Request submitted", Toast.LENGTH_SHORT).show()
                    etRequestType.setText("", false)
                    etDescription.setText("")
                    loadRequests()

                } catch (e: Exception) {
                    Toast.makeText(requireContext(), "Error: ${e.message}", Toast.LENGTH_SHORT).show()
                } finally {
                    btnSubmit.isEnabled = true
                }
            }
        }

        swipeRefresh = view.findViewById(R.id.swipeRefresh)
        swipeRefresh.setColorSchemeResources(R.color.aqua_navy)
        swipeRefresh.setOnRefreshListener {
            viewLifecycleOwner.lifecycleScope.launch {
                loadServiceLocation()
                loadRequests()
            }
        }

        viewLifecycleOwner.lifecycleScope.launch {
            loadServiceLocation()
            loadRequests()
        }
    }

    private suspend fun loadServiceLocation() {
        try {
            showServiceLocation(Consumer.load(userCode))
        } catch (e: Exception) {
            requireView().findViewById<TextView>(R.id.tvServiceAddress).text = "Couldn't load your address"
        }
    }

    // Shows the address and meter number that will be sent with the request
    private fun showServiceLocation(consumer: Consumer?) {
        val root = requireView()
        root.findViewById<TextView>(R.id.tvServiceAddress).text =
            consumer?.address?.takeIf { it.isNotBlank() } ?: "No address on file"
        root.findViewById<TextView>(R.id.tvMeterNo).text =
            "Meter no. ${consumer?.meter_no?.takeIf { it.isNotBlank() } ?: "—"}"
    }

    private suspend fun loadRequests() {
        // Pull-to-refresh shows its own spinner
        progress.isVisible = !swipeRefresh.isRefreshing
        try {
            val requests = supabase
                .from("maintenance_requests")
                .select {
                    filter { eq("user_code", userCode) }
                    order("created_at", Order.DESCENDING)
                }
                .decodeList<MaintenanceRequest>()
            renderRequests(requests)

        } catch (e: Exception) {
            Toast.makeText(requireContext(), "Error: ${e.message}", Toast.LENGTH_SHORT).show()
        } finally {
            progress.isVisible = false
            swipeRefresh.isRefreshing = false
        }
    }

    private fun renderRequests(requests: List<MaintenanceRequest>) {
        requestList.removeAllViews()
        val inflater = LayoutInflater.from(requireContext())
        requests.forEach { request ->
            val item = inflater.inflate(R.layout.item_maintenance_request, requestList, false)
            item.findViewById<TextView>(R.id.tvRequestType).text = request.request_type ?: "Request"
            item.findViewById<TextView>(R.id.tvRequestDate).text =
                formatDate(request.created_at, "MMM d, yyyy")
            item.findViewById<TextView>(R.id.tvRequestDescription).text = request.description.orEmpty()
            item.findViewById<TextView>(R.id.tvRequestStatus).showRequestStatus(request.status)
            requestList.addView(item)
        }
        tvEmpty.isVisible = requests.isEmpty()
    }
}
