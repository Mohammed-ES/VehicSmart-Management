<?php
// Get current page
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="w-64 bg-white shadow-md h-screen sticky top-0 overflow-auto hidden md:block">
    <div class="p-6 border-b">
        <a href="dashboard.php" class="text-xl font-bold text-gray-800">Admin Panel</a>
    </div>
    
    <nav class="p-4">
        <ul class="space-y-1">
            <li>
                <a href="dashboard.php" class="flex items-center px-4 py-3 rounded-lg <?= $currentPage === 'dashboard.php' ? 'bg-accent text-white' : 'hover:bg-gray-100' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </a>
            </li>
            
            <li class="mt-6 border-t pt-4">
                <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Vehicles</p>
            </li>
            <li>
                <a href="vehicles.php" class="flex items-center px-4 py-3 rounded-lg <?= $currentPage === 'vehicles.php' ? 'bg-accent text-white' : 'hover:bg-gray-100' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                    All Vehicles
                </a>
            </li>
            <li>
                <a href="add_vehicle.php" class="flex items-center px-4 py-3 rounded-lg <?= $currentPage === 'add_vehicle.php' ? 'bg-accent text-white' : 'hover:bg-gray-100' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Vehicle
                </a>
            </li>
            
            <li class="mt-6 border-t pt-4">
                <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Rentals</p>
            </li>
            <li>
                <a href="rentals.php" class="flex items-center px-4 py-3 rounded-lg <?= $currentPage === 'rentals.php' ? 'bg-accent text-white' : 'hover:bg-gray-100' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    All Rentals
                </a>
            </li>
            
            <li class="mt-6 border-t pt-4">
                <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Users</p>
            </li>
            <li>
                <a href="users.php" class="flex items-center px-4 py-3 rounded-lg <?= $currentPage === 'users.php' ? 'bg-accent text-white' : 'hover:bg-gray-100' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    All Users
                </a>
            </li>
            
            <li class="mt-6 border-t pt-4">
                <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Maintenance</p>
            </li>
            <li>
                <a href="maintenance.php" class="flex items-center px-4 py-3 rounded-lg <?= $currentPage === 'maintenance.php' ? 'bg-accent text-white' : 'hover:bg-gray-100' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Maintenance
                </a>
            </li>
            
            <!-- Communication section removed - messaging system deleted -->
            
            <li class="mt-6 border-t pt-4">
                <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">Management</p>
            </li>
            <li>
                <a href="clients_manage.php" class="flex items-center px-4 py-3 rounded-lg <?= $currentPage === 'clients_manage.php' ? 'bg-accent text-white' : 'hover:bg-gray-100' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    Clients
                </a>
            </li>
            <li>
                <a href="vehicles_manage.php" class="flex items-center px-4 py-3 rounded-lg <?= $currentPage === 'vehicles_manage.php' ? 'bg-accent text-white' : 'hover:bg-gray-100' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                    </svg>
                    Vehicles
                </a>
            </li>
            <li>
                <a href="reports.php" class="flex items-center px-4 py-3 rounded-lg <?= $currentPage === 'reports.php' ? 'bg-accent text-white' : 'hover:bg-gray-100' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Reports
                </a>
            </li>
            
            <li class="mt-6 border-t pt-4">
                <p class="px-4 text-xs font-semibold text-gray-400 uppercase tracking-wider">System</p>
            </li>
            <li>
                <a href="system_diagnostic.php" class="flex items-center px-4 py-3 rounded-lg <?= $currentPage === 'system_diagnostic.php' ? 'bg-accent text-white' : 'hover:bg-gray-100' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Diagnostic
                </a>
            </li>
            <li>
                <a href="settings.php" class="flex items-center px-4 py-3 rounded-lg <?= $currentPage === 'settings.php' ? 'bg-accent text-white' : 'hover:bg-gray-100' ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Settings
                </a>
            </li>
            
            <li class="mt-6 border-t pt-4">
                <a href="../auth/logout.php" class="flex items-center px-4 py-3 rounded-lg text-red-500 hover:bg-red-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                </a>
            </li>
        </ul>
    </nav>
</aside>
