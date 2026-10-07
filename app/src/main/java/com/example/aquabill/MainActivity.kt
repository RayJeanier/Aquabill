package com.example.aquabill

import android.content.res.ColorStateList
import android.graphics.Typeface
import android.os.Bundle
import android.view.View
import android.view.ViewGroup
import android.widget.ImageView
import android.widget.TextView
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import androidx.core.view.ViewCompat
import androidx.core.view.WindowInsetsCompat
import androidx.core.view.children
import androidx.core.view.isVisible
import androidx.core.view.updateLayoutParams
import androidx.core.widget.ImageViewCompat
import androidx.navigation.NavController
import androidx.navigation.fragment.NavHostFragment
import androidx.navigation.navOptions
import io.github.jan.supabase.createSupabaseClient
import io.github.jan.supabase.postgrest.Postgrest


val supabase = createSupabaseClient(
    supabaseUrl = "https://stjcqrzvctzxtxnqwnfe.supabase.co",
    supabaseKey = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InN0amNxcnp2Y3R6eHR4bnF3bmZlIiwicm9sZSI6ImFub24iLCJpYXQiOjE3Nzk3MjIyODUsImV4cCI6MjA5NTI5ODI4NX0.lK3iTvkNGhpUAx8xWVtNeE657fFE9I7Fn28RoRXjdZs"
) {
    install(Postgrest)
}
class MainActivity : AppCompatActivity() {

    private lateinit var navController: NavController
    private lateinit var bottomNav: View

    // Bottom nav tab -> destination it opens
    private val tabs by lazy {
        mapOf(
            R.id.navHome to R.id.userHomeFragment,
            R.id.navBills to R.id.billsFragment,
            R.id.navService to R.id.maintenanceFragment,
            R.id.navHistory to R.id.historyFragment
        )
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)

        val navHost = supportFragmentManager.findFragmentById(R.id.nav_host_fragment) as NavHostFragment
        navController = navHost.navController
        bottomNav = findViewById(R.id.bottomNav)

        tabs.forEach { (tabId, destinationId) ->
            findViewById<View>(tabId).setOnClickListener { openTab(destinationId) }
        }

        navController.addOnDestinationChangedListener { _, destination, _ ->
            bottomNav.isVisible = destination.id in tabs.values
            tabs.forEach { (tabId, destinationId) ->
                styleTab(findViewById(tabId), selected = destination.id == destinationId)
            }
        }

        // Lift the bar above the gesture bar; hide it while the keyboard is open
        val baseMargin = (bottomNav.layoutParams as ViewGroup.MarginLayoutParams).bottomMargin
        ViewCompat.setOnApplyWindowInsetsListener(bottomNav) { v, insets ->
            val navBar = insets.getInsets(WindowInsetsCompat.Type.navigationBars()).bottom
            v.updateLayoutParams<ViewGroup.MarginLayoutParams> { bottomMargin = baseMargin + navBar }
            val keyboardOpen = insets.isVisible(WindowInsetsCompat.Type.ime())
            v.isVisible = !keyboardOpen && navController.currentDestination?.id in tabs.values
            insets
        }
    }

    // Opens a bottom-nav destination; also used by in-page links like History's "View bills"
    fun openTab(destinationId: Int) {
        if (navController.currentDestination?.id == destinationId) return
        if (destinationId == R.id.userHomeFragment) {
            navController.popBackStack(R.id.userHomeFragment, false)
            return
        }
        // Home stays at the bottom of the stack, so Back from any tab returns there
        val home = navController.getBackStackEntry(R.id.userHomeFragment)
        val args = Bundle().apply { putString("user_code", home.arguments?.getString("user_code")) }
        navController.navigate(destinationId, args, navOptions {
            popUpTo(R.id.userHomeFragment)
            launchSingleTop = true
        })
    }

    private fun styleTab(tab: ViewGroup, selected: Boolean) {
        val color = ContextCompat.getColor(this, if (selected) R.color.aqua_navy else R.color.text_secondary)
        tab.children.forEach { child ->
            when (child) {
                is ImageView -> ImageViewCompat.setImageTintList(child, ColorStateList.valueOf(color))
                is TextView -> {
                    child.setTextColor(color)
                    child.setTypeface(null, if (selected) Typeface.BOLD else Typeface.NORMAL)
                }
                else -> child.isVisible = selected // selection dot
            }
        }
    }
}