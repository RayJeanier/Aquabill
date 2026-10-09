package com.example.aquabill

import android.animation.Animator
import android.animation.ObjectAnimator
import android.animation.PropertyValuesHolder
import android.animation.ValueAnimator
import android.os.Bundle
import android.os.SystemClock
import android.view.View
import android.view.animation.AccelerateDecelerateInterpolator
import android.view.animation.DecelerateInterpolator
import android.view.animation.PathInterpolator
import android.view.inputmethod.EditorInfo
import android.widget.Button
import android.widget.TextView
import android.widget.Toast
import androidx.core.view.ViewCompat
import androidx.core.view.WindowCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.view.isVisible
import androidx.core.view.updatePadding
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import com.google.android.material.textfield.TextInputEditText
import io.github.jan.supabase.postgrest.from
import io.github.jan.supabase.postgrest.postgrest
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import kotlinx.serialization.json.buildJsonObject
import kotlinx.serialization.json.put

class LogInFragment : Fragment(R.layout.fragment_log_in) {

    private lateinit var loadingScreen: View
    private lateinit var dropLoader: DropLoaderView
    private lateinit var tvLoadingTitle: TextView
    private lateinit var tvLoadingSub: TextView
    private var loadingText: Job? = null
    private var loadingShownAt = 0L

    // Looping logo animations, stopped when the screen closes
    private val logoAnimators = mutableListOf<Animator>()

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val etAccount = view.findViewById<TextInputEditText>(R.id.etAccount)
        val etPassword = view.findViewById<TextInputEditText>(R.id.etPassword)
        val btnLogin = view.findViewById<Button>(R.id.btnLogin)

        loadingScreen = view.findViewById(R.id.loadingScreen)
        dropLoader = view.findViewById(R.id.dropLoader)
        tvLoadingTitle = view.findViewById(R.id.tvLoadingTitle)
        tvLoadingSub = view.findViewById(R.id.tvLoadingSub)

        // Keep the page clear of the status bar, and scrollable above the keyboard
        val content = view.findViewById<View>(R.id.loginContent)
        val contentTop = content.paddingTop
        val contentBottom = content.paddingBottom
        ViewCompat.setOnApplyWindowInsetsListener(content) { v, insets ->
            val bottom = maxOf(
                insets.getInsets(WindowInsetsCompat.Type.navigationBars()).bottom,
                insets.getInsets(WindowInsetsCompat.Type.ime()).bottom
            )
            v.updatePadding(
                top = contentTop + insets.getInsets(WindowInsetsCompat.Type.statusBars()).top,
                bottom = contentBottom + bottom
            )
            insets
        }

        if (savedInstanceState == null) playIntro(view)
        animateLogo(view)

        // "Done" on the keyboard signs in
        etPassword.setOnEditorActionListener { _, actionId, _ ->
            if (actionId == EditorInfo.IME_ACTION_DONE) btnLogin.performClick()
            actionId == EditorInfo.IME_ACTION_DONE
        }

        btnLogin.setOnClickListener {

            val user_id = etAccount.text.toString().trim()
            val password = etPassword.text.toString().trim()

            if (user_id.isEmpty() || password.isEmpty()) {
                Toast.makeText(requireContext(), "Fill all fields", Toast.LENGTH_SHORT).show()
                return@setOnClickListener
            }

            loginUser(user_id, password)
        }
    }

    private fun loginUser(user_id: String, password: String) {

        showLoading()

        viewLifecycleOwner.lifecycleScope.launch {

            try {

                // Log in with the account number (user code) or the username. Passwords are
                // bcrypt hashes, so the app_login() database function checks them (the same
                // way the website does) and returns the account without the password.
                val matchedUser = if (!LOGIN_ID.matches(user_id)) null else supabase.postgrest
                    .rpc(
                        "app_login",
                        buildJsonObject {
                            put("p_login", user_id)
                            put("p_password", password)
                        }
                    )
                    .decodeList<User>()
                    .firstOrNull()

                if (matchedUser != null) {

                    val role = matchedUser.role

                    // Consumers' display names live in the consumers table
                    val name = if (role == "consumer") {
                        supabase
                            .from("consumers")
                            .select { filter { eq("user_code", matchedUser.user_code) } }
                            .decodeList<Consumer>()
                            .firstOrNull()
                            ?.name
                    } else {
                        null
                    }
                    val displayName = name ?: matchedUser.user_code

                    val destination = when (role) {
                        "reader" -> R.id.action_logInFragment_to_readerHomeFragment
                        "consumer" -> R.id.action_logInFragment_to_userHomeFragment
                        "plumber" -> R.id.action_logInFragment_to_plumberHomeFragment
                        else -> null
                    }
                    if (destination == null) {
                        hideLoading()
                        Toast.makeText(requireContext(), "Unknown role: $role", Toast.LENGTH_SHORT).show()
                        return@launch
                    }

                    // Let the drop fill a little before moving on, so the screen doesn't just flash
                    tvLoadingTitle.text = "Welcome"
                    tvLoadingSub.text = displayName
                    loadingText?.cancel()
                    delay((MIN_LOADING_MS - (SystemClock.uptimeMillis() - loadingShownAt)).coerceAtLeast(400))

                    val bundle = Bundle().apply {
                        putString("role", role)
                        putString("user_code", matchedUser.user_code)
                        putString("name", displayName)
                    }
                    findNavController().navigate(destination, bundle)

                } else {
                    hideLoading()
                    Toast.makeText(
                        requireContext(),
                        "Wrong credentials",
                        Toast.LENGTH_SHORT
                    ).show()
                }

            } catch (e: Exception) {

                hideLoading()
                Toast.makeText(
                    requireContext(),
                    "Error: ${e.message}",
                    Toast.LENGTH_SHORT
                ).show()
            }
        }
    }

    override fun onDestroyView() {
        logoAnimators.forEach { it.cancel() }
        logoAnimators.clear()
        super.onDestroyView()
    }

    // The website's entrance: logo drops in, heading lines rise one after another, then the text
    private fun playIntro(view: View) {
        val dp = resources.displayMetrics.density
        val easeOut = PathInterpolator(0.2f, 0.8f, 0.2f, 1f)

        fun View.enter(fromY: Float, delayMs: Long, durationMs: Long, toAlpha: Float = 1f) {
            alpha = 0f
            translationY = fromY * dp
            animate().alpha(toAlpha).translationY(0f)
                .setStartDelay(delayMs).setDuration(durationMs).setInterpolator(easeOut).start()
        }

        view.findViewById<View>(R.id.logoRow).enter(-24f, 0, 800)
        view.findViewById<View>(R.id.headLine1).enter(28f, 250, 800)
        view.findViewById<View>(R.id.headLine2).enter(28f, 400, 800)
        view.findViewById<View>(R.id.headLine3).enter(28f, 550, 800)
        view.findViewById<View>(R.id.tvIntro).enter(16f, 750, 900, toAlpha = 0.9f)
        view.findViewById<View>(R.id.loginCard).enter(24f, 300, 800)
    }

    // Logo floats gently, with rings spreading out from it like a drop hitting water
    private fun animateLogo(view: View) {
        val dp = resources.displayMetrics.density
        ObjectAnimator.ofFloat(view.findViewById(R.id.logoMark), View.TRANSLATION_Y, 0f, -6f * dp, 0f).apply {
            duration = 4000
            startDelay = 1000
            repeatCount = ValueAnimator.INFINITE
            interpolator = AccelerateDecelerateInterpolator()
            start()
            logoAnimators += this
        }
        listOf(R.id.logoRipple1 to 1000L, R.id.logoRipple2 to 2600L).forEach { (id, delayMs) ->
            val ring = view.findViewById<View>(id)
            ObjectAnimator.ofPropertyValuesHolder(
                ring,
                PropertyValuesHolder.ofFloat(View.SCALE_X, 1f, 1.9f),
                PropertyValuesHolder.ofFloat(View.SCALE_Y, 1f, 1.9f),
                PropertyValuesHolder.ofFloat(View.ALPHA, 0.8f, 0f)
            ).apply {
                duration = 3200
                startDelay = delayMs
                repeatCount = ValueAnimator.INFINITE
                interpolator = DecelerateInterpolator()
                start()
                logoAnimators += this
            }
        }
    }

    // Full-screen drop filling with water while signing in (same as the website's)
    private fun showLoading() {
        WindowCompat.getInsetsController(requireActivity().window, requireView())
            .hide(WindowInsetsCompat.Type.ime())

        loadingShownAt = SystemClock.uptimeMillis()
        tvLoadingSub.text = "Checking your account"
        dropLoader.restart()
        loadingScreen.alpha = 0f
        loadingScreen.isVisible = true
        loadingScreen.animate().alpha(1f).setDuration(300).start()

        // "Signing you in", "Signing you in.", "..", "...", and a note if it's slow
        loadingText = viewLifecycleOwner.lifecycleScope.launch {
            var ticks = 0
            while (true) {
                tvLoadingTitle.text = "Signing you in" + ".".repeat(ticks % 4)
                when (ticks) {
                    9 -> tvLoadingSub.text = "Still working on it"
                    20 -> tvLoadingSub.text = "This is taking a while. Check your internet connection."
                }
                ticks++
                delay(450)
            }
        }
    }

    private fun hideLoading() {
        loadingText?.cancel()
        loadingScreen.animate().alpha(0f).setDuration(200).withEndAction {
            loadingScreen.isVisible = false
        }.start()
    }

    private companion object {
        // User codes look like SVOB-CONS-37E7C1; usernames are letters, digits, . _ - @
        val LOGIN_ID = Regex("""[A-Za-z0-9._@-]+""")

        // Shortest time the loading screen stays up after a successful login
        const val MIN_LOADING_MS = 1600L
    }
}
