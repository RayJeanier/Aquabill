package com.example.aquabill

import android.os.Bundle
import android.view.LayoutInflater
import android.view.View
import android.view.ViewGroup
import android.widget.LinearLayout
import android.widget.ProgressBar
import android.widget.TextView
import android.widget.Toast
import androidx.core.os.bundleOf
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.view.isVisible
import androidx.core.view.updateLayoutParams
import androidx.core.view.updatePadding
import androidx.core.widget.doAfterTextChanged
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import androidx.swiperefreshlayout.widget.SwipeRefreshLayout
import com.google.android.material.textfield.TextInputEditText
import com.journeyapps.barcodescanner.ScanContract
import com.journeyapps.barcodescanner.ScanOptions
import io.github.jan.supabase.postgrest.from
import io.github.jan.supabase.postgrest.query.Order
import kotlinx.coroutines.launch

// Meter reader home: every consumer with their address, meter number and last reading.
// Tap a consumer, or scan the QR code on their meter, to record a new reading.
class ReaderHomeFragment : Fragment(R.layout.fragment_reader_home) {

    private var consumers: List<Consumer> = emptyList()
    private var latestReadings: Map<String, Reading> = emptyMap()

    private lateinit var etSearch: TextInputEditText
    private lateinit var consumerList: LinearLayout
    private lateinit var tvEmpty: TextView
    private lateinit var progress: ProgressBar
    private lateinit var swipeRefresh: SwipeRefreshLayout

    private val scanQr = registerForActivityResult(ScanContract()) { result ->
        result.contents?.let { openScanned(it.trim()) }
    }

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val name = arguments?.getString("name") ?: arguments?.getString("user_code").orEmpty()
        view.findViewById<TextView>(R.id.tvGreeting).text = "${greetingForNow()},"
        view.findViewById<TextView>(R.id.tvName).text = name
        view.findViewById<View>(R.id.btnLogout).setOnClickListener { confirmLogout() }

        etSearch = view.findViewById(R.id.etSearch)
        consumerList = view.findViewById(R.id.consumerList)
        tvEmpty = view.findViewById(R.id.tvEmpty)
        progress = view.findViewById(R.id.progress)
        swipeRefresh = view.findViewById(R.id.swipeRefresh)

        etSearch.doAfterTextChanged { renderList() }

        swipeRefresh.setColorSchemeResources(R.color.aqua_navy)
        swipeRefresh.setOnRefreshListener { loadConsumers() }

        view.findViewById<View>(R.id.btnScanQr).setOnClickListener {
            scanQr.launch(
                ScanOptions()
                    .setDesiredBarcodeFormats(ScanOptions.QR_CODE)
                    .setPrompt("Scan the consumer's QR code")
                    .setBeepEnabled(false)
                    .setOrientationLocked(false)
            )
        }

        applyWindowInsets(view)
        loadConsumers()
    }

    private fun loadConsumers() {
        viewLifecycleOwner.lifecycleScope.launch {
            // Pull-to-refresh shows its own spinner
            progress.isVisible = !swipeRefresh.isRefreshing
            try {
                consumers = supabase
                    .from("consumers")
                    .select { order("name", Order.ASCENDING) }
                    .decodeList<Consumer>()
                latestReadings = Reading.loadLatest()
                renderProgress()
                renderList()

            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            } finally {
                progress.isVisible = false
                swipeRefresh.isRefreshing = false
            }
        }
    }

    private fun renderProgress() {
        val read = consumers.count { latestReadings[it.user_code]?.isThisMonth == true }
        requireView().findViewById<TextView>(R.id.tvProgress).text =
            "Meter reading · $read of ${consumers.size} read this month"
    }

    private fun renderList() {
        val query = etSearch.text?.toString()?.trim().orEmpty()
        val shown = consumers.filter { consumer ->
            query.isEmpty() || listOfNotNull(consumer.name, consumer.user_code, consumer.address, consumer.meter_no)
                .any { it.contains(query, ignoreCase = true) }
        }

        consumerList.removeAllViews()
        val inflater = LayoutInflater.from(requireContext())
        shown.forEach { consumer -> consumerList.addView(consumerItem(inflater, consumerList, consumer)) }
        tvEmpty.isVisible = shown.isEmpty()
    }

    private fun consumerItem(inflater: LayoutInflater, parent: ViewGroup, consumer: Consumer): View {
        val item = inflater.inflate(R.layout.item_reader_consumer, parent, false)
        val latest = latestReadings[consumer.user_code]

        item.findViewById<TextView>(R.id.tvConsumerName).text = consumer.name ?: consumer.user_code
        item.findViewById<TextView>(R.id.tvUserCode).text = consumer.user_code
        item.findViewById<TextView>(R.id.tvLocation).text = consumer.locationText()
        item.findViewById<TextView>(R.id.tvPreviousReading).text =
            latest?.current_reading?.let { formatCubicMeters(it) } ?: "No readings yet"
        item.findViewById<TextView>(R.id.tvPreviousDate).text =
            latest?.let { formatDate(it.reading_date, "MMM dd, yyyy") }.orEmpty()

        val status = item.findViewById<TextView>(R.id.tvReadStatus)
        if (latest?.isThisMonth == true) {
            status.showStatusBadge("Read", R.color.success_green, R.color.success_green_bg)
        } else {
            status.showStatusBadge("Not yet read", R.color.warning_orange, R.color.warning_orange_bg)
        }

        item.setOnClickListener { openConsumer(consumer.user_code) }
        return item
    }

    // The QR code holds the consumer's user code (meter number also accepted)
    private fun openScanned(text: String) {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                if (consumers.isEmpty()) {
                    consumers = supabase.from("consumers").select().decodeList<Consumer>()
                }
                val match = consumers.firstOrNull { it.user_code.equals(text, ignoreCase = true) }
                    ?: consumers.firstOrNull { it.meter_no == text }
                    ?: consumers.firstOrNull { text.contains(it.user_code, ignoreCase = true) }

                if (match == null) {
                    Toast.makeText(requireContext(), "No consumer found for \"$text\"", Toast.LENGTH_LONG).show()
                } else {
                    openConsumer(match.user_code)
                }
            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun openConsumer(userCode: String) {
        findNavController().navigate(
            R.id.action_readerHomeFragment_to_meterReadingFragment,
            bundleOf("user_code" to userCode)
        )
    }

    // Keep the header clear of the status bar and the scan button above the gesture bar
    private fun applyWindowInsets(view: View) {
        val header = view.findViewById<View>(R.id.header)
        val scanButton = view.findViewById<View>(R.id.btnScanQr)
        val headerTop = header.paddingTop
        val scanBottom = (scanButton.layoutParams as ViewGroup.MarginLayoutParams).bottomMargin
        val spinnerDistance = (64 * resources.displayMetrics.density).toInt()

        ViewCompat.setOnApplyWindowInsetsListener(view) { _, insets ->
            val bars = insets.getInsets(WindowInsetsCompat.Type.systemBars())
            header.updatePadding(top = headerTop + bars.top)
            swipeRefresh.setProgressViewOffset(false, bars.top, bars.top + spinnerDistance)
            scanButton.updateLayoutParams<ViewGroup.MarginLayoutParams> { bottomMargin = scanBottom + bars.bottom }
            // Hide the scan button while typing a search
            scanButton.isVisible = !insets.isVisible(WindowInsetsCompat.Type.ime())
            insets
        }
    }
}
