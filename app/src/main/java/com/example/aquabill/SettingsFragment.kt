package com.example.aquabill

import android.os.Bundle
import android.util.Log
import android.view.View
import android.widget.TextView
import android.widget.Toast
import androidx.core.view.WindowCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.widget.doAfterTextChanged
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import com.google.android.material.button.MaterialButton
import com.google.android.material.dialog.MaterialAlertDialogBuilder
import com.google.android.material.textfield.TextInputEditText
import com.google.android.material.textfield.TextInputLayout
import io.github.jan.supabase.postgrest.from
import io.github.jan.supabase.postgrest.postgrest
import io.github.jan.supabase.postgrest.query.Columns
import kotlinx.coroutines.launch
import kotlinx.serialization.Serializable
import kotlinx.serialization.json.buildJsonObject
import kotlinx.serialization.json.put

// Consumer settings: account details, the username they can log in with, and the password.
// Both are changed through database functions (change_username / change_password) that check
// the current password first (the app can't write the users table directly).
class SettingsFragment : Fragment(R.layout.fragment_settings) {

    private lateinit var userCode: String

    private lateinit var tvUsernameCurrent: TextView
    private lateinit var tilUsername: TextInputLayout
    private lateinit var tilUsernamePassword: TextInputLayout
    private lateinit var etUsername: TextInputEditText
    private lateinit var etUsernamePassword: TextInputEditText
    private lateinit var btnChangeUsername: MaterialButton

    private lateinit var tilCurrent: TextInputLayout
    private lateinit var tilNew: TextInputLayout
    private lateinit var tilConfirm: TextInputLayout
    private lateinit var etCurrent: TextInputEditText
    private lateinit var etNew: TextInputEditText
    private lateinit var etConfirm: TextInputEditText
    private lateinit var btnChange: MaterialButton

    @Serializable
    private data class UsernameRow(val username: String? = null)

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        userCode = arguments?.getString("user_code").orEmpty()
        setupScreenHeader(view, "Settings")

        tvUsernameCurrent = view.findViewById(R.id.tvUsernameCurrent)
        tilUsername = view.findViewById(R.id.tilUsername)
        tilUsernamePassword = view.findViewById(R.id.tilUsernamePassword)
        etUsername = view.findViewById(R.id.etUsername)
        etUsernamePassword = view.findViewById(R.id.etUsernamePassword)
        btnChangeUsername = view.findViewById(R.id.btnChangeUsername)

        tilCurrent = view.findViewById(R.id.tilCurrentPassword)
        tilNew = view.findViewById(R.id.tilNewPassword)
        tilConfirm = view.findViewById(R.id.tilConfirmPassword)
        etCurrent = view.findViewById(R.id.etCurrentPassword)
        etNew = view.findViewById(R.id.etNewPassword)
        etConfirm = view.findViewById(R.id.etConfirmPassword)
        btnChange = view.findViewById(R.id.btnChangePassword)

        // Clear a field's error as soon as it's edited
        listOf(
            tilUsername to etUsername, tilUsernamePassword to etUsernamePassword,
            tilCurrent to etCurrent, tilNew to etNew, tilConfirm to etConfirm
        ).forEach { (til, et) ->
            et.doAfterTextChanged { til.error = null }
        }

        btnChangeUsername.setOnClickListener { changeUsername() }
        btnChange.setOnClickListener { changePassword() }
        view.findViewById<View>(R.id.btnSettingsLogout).setOnClickListener { confirmLogout() }

        view.findViewById<TextView>(R.id.tvAccountCode).text = "Account no. $userCode"
        showUsername(null)
        loadAccount(view)
    }

    private fun loadAccount(view: View) {
        viewLifecycleOwner.lifecycleScope.launch {
            try {
                val username = supabase
                    .from("users")
                    .select(Columns.list("username")) { filter { eq("user_code", userCode) } }
                    .decodeList<UsernameRow>()
                    .firstOrNull()
                    ?.username
                showUsername(username)

                val consumer = Consumer.load(userCode) ?: return@launch
                view.findViewById<TextView>(R.id.tvAccountName).text = consumer.name ?: userCode
                view.findViewById<TextView>(R.id.tvAccountLocation).text = consumer.locationText()
            } catch (e: Exception) {
                Toast.makeText(requireContext(), "Error: ${e.message}", Toast.LENGTH_SHORT).show()
            }
        }
    }

    private fun showUsername(username: String?) {
        tvUsernameCurrent.text = if (username.isNullOrBlank()) {
            "Not set yet. Add one to log in without your account number."
        } else {
            "Log in as $username or with your account number"
        }
        btnChangeUsername.text = if (username.isNullOrBlank()) "Set username" else "Change username"
    }

    private fun changeUsername() {
        val username = etUsername.text?.toString()?.trim()?.lowercase().orEmpty()
        val password = etUsernamePassword.text?.toString().orEmpty()

        // Same rules as the change_username() database function
        var valid = true
        if (!USERNAME.matches(username) || username.startsWith("svob-")) {
            tilUsername.error = "Use 3–30 letters, numbers, . _ or -"
            valid = false
        }
        if (password.isEmpty()) {
            tilUsernamePassword.error = "Enter your current password"
            valid = false
        }
        if (!valid) return

        hideKeyboard()
        viewLifecycleOwner.lifecycleScope.launch {
            btnChangeUsername.isEnabled = false
            try {
                val result = supabase.postgrest
                    .rpc(
                        "change_username",
                        buildJsonObject {
                            put("p_user_code", userCode)
                            put("p_password", password)
                            put("p_username", username)
                        }
                    )
                    .decodeAs<String>()

                when (result) {
                    "ok" -> {
                        listOf(etUsername, etUsernamePassword).forEach { it.text = null }
                        showUsername(username)
                        MaterialAlertDialogBuilder(requireContext())
                            .setTitle("Username saved")
                            .setMessage("You can now log in as \"$username\" or with your account number.")
                            .setPositiveButton("OK", null)
                            .show()
                    }
                    "taken" -> tilUsername.error = "That username is already taken"
                    "wrong_password" -> tilUsernamePassword.error = "Password is incorrect"
                    else -> tilUsername.error = "Use 3–30 letters, numbers, . _ or -"
                }

            } catch (e: Exception) {
                Log.e("Settings", "Changing username failed", e)
                Toast.makeText(requireContext(), "Couldn't change username: ${e.message}", Toast.LENGTH_LONG).show()
            } finally {
                btnChangeUsername.isEnabled = true
            }
        }
    }

    private fun changePassword() {
        val current = etCurrent.text?.toString().orEmpty()
        val new = etNew.text?.toString().orEmpty()
        val confirm = etConfirm.text?.toString().orEmpty()

        // Same rules as the web's staff passwords (action/add_staff.php)
        var valid = true
        if (current.isEmpty()) {
            tilCurrent.error = "Enter your current password"
            valid = false
        }
        if (new.length < MIN_PASSWORD_LENGTH) {
            tilNew.error = "Use at least $MIN_PASSWORD_LENGTH characters"
            valid = false
        } else if (new == current) {
            tilNew.error = "Choose a different password from your current one"
            valid = false
        }
        if (confirm != new) {
            tilConfirm.error = "Passwords don't match"
            valid = false
        }
        if (!valid) return

        hideKeyboard()
        viewLifecycleOwner.lifecycleScope.launch {
            btnChange.isEnabled = false
            try {
                val changed = supabase.postgrest
                    .rpc(
                        "change_password",
                        buildJsonObject {
                            put("p_user_code", userCode)
                            put("p_current", current)
                            put("p_new", new)
                        }
                    )
                    .decodeAs<Boolean>()

                if (!changed) {
                    tilCurrent.error = "Current password is incorrect"
                    return@launch
                }

                listOf(etCurrent, etNew, etConfirm).forEach { it.text = null }
                MaterialAlertDialogBuilder(requireContext())
                    .setTitle("Password updated")
                    .setMessage("Use your new password the next time you log in, on the app or the website.")
                    .setPositiveButton("OK", null)
                    .show()

            } catch (e: Exception) {
                Log.e("Settings", "Changing password failed", e)
                Toast.makeText(requireContext(), "Couldn't change password: ${e.message}", Toast.LENGTH_LONG).show()
            } finally {
                btnChange.isEnabled = true
            }
        }
    }

    private fun hideKeyboard() {
        val view = view ?: return
        WindowCompat.getInsetsController(requireActivity().window, view).hide(WindowInsetsCompat.Type.ime())
    }

    private companion object {
        const val MIN_PASSWORD_LENGTH = 6
        val USERNAME = Regex("""[a-z0-9._-]{3,30}""")
    }
}
