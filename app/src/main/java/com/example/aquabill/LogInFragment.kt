package com.example.aquabill

import android.os.Bundle
import android.view.View
import android.widget.Button
import android.widget.Toast
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.view.updatePadding
import androidx.fragment.app.Fragment
import androidx.lifecycle.lifecycleScope
import androidx.navigation.fragment.findNavController
import com.google.android.material.textfield.TextInputEditText
import io.github.jan.supabase.postgrest.from
import kotlinx.coroutines.launch

class LogInFragment : Fragment(R.layout.fragment_log_in) {

    override fun onViewCreated(view: View, savedInstanceState: Bundle?) {
        super.onViewCreated(view, savedInstanceState)

        val etAccount = view.findViewById<TextInputEditText>(R.id.etAccount)
        val etPassword = view.findViewById<TextInputEditText>(R.id.etPassword)
        val btnLogin = view.findViewById<Button>(R.id.btnLogin)

        // Keep the login sheet clear of the gesture/navigation bar
        val sheet = view.findViewById<View>(R.id.loginSheet)
        val sheetBottom = sheet.paddingBottom
        ViewCompat.setOnApplyWindowInsetsListener(sheet) { v, insets ->
            v.updatePadding(bottom = sheetBottom + insets.getInsets(WindowInsetsCompat.Type.navigationBars()).bottom)
            insets
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

        lifecycleScope.launch {

            try {

                val users = supabase
                    .from("users")
                    .select {
                        filter {
                            eq("user_code", user_id)
                            eq("password", password)
                        }
                    }
                    .decodeList<User>()

                val matchedUser = users.firstOrNull()

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

                    Toast.makeText(
                        requireContext(),
                        "Welcome $displayName",
                        Toast.LENGTH_SHORT
                    ).show()

                    val bundle = Bundle().apply {
                        putString("role", role)
                        putString("user_code", matchedUser.user_code)
                        putString("name", displayName)
                    }

                    when (role) {

                        "reader" -> findNavController().navigate(
                            R.id.action_logInFragment_to_readerHomeFragment,
                            bundle
                        )

                        "consumer" -> findNavController().navigate(
                            R.id.action_logInFragment_to_userHomeFragment,
                            bundle
                        )

                        else -> Toast.makeText(
                            requireContext(),
                            "Unknown role: $role",
                            Toast.LENGTH_SHORT
                        ).show()
                    }

                } else {
                    Toast.makeText(
                        requireContext(),
                        "Wrong credentials",
                        Toast.LENGTH_SHORT
                    ).show()
                }

            } catch (e: Exception) {

                Toast.makeText(
                    requireContext(),
                    "Error: ${e.message}",
                    Toast.LENGTH_SHORT
                ).show()
            }
        }
    }
}



