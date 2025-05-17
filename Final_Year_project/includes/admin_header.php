<!-- Header -->
<header class="bg-white shadow">
  <div class="max-w-7xl mx-auto px-4 py-4 sm:px-6 lg:px-8 flex justify-between items-center">
    <!-- Logo/Dashboard Title -->
    <div class="flex items-center space-x-8">
      <h1 class="text-2xl font-bold text-gray-900"><a href="dashboard.php" class="...">Admin Dashboard</a></h1>

    <nav class="hidden md:flex space-x-10 items-center"> 
      <a href="../admin/sales.php" class="text-gray-600 hover:text-indigo-600 transition-colors font-bold mx-0 px-0">Sales</a>
      <a href="../admin/transaction.php"class="text-gray-600 hover:text-indigo-600 transition-colors font-bold mx-0 px-0">Transactions</a>
      <a href="../admin/product.php" class="text-gray-600 hover:text-indigo-600 transition-colors font-bold mx-0 px-0">Products</a>
      <a href="../admin/manage_student_balance.php" class="text-gray-600 hover:text-indigo-600 transition-colors font-bold">Manage Balances</a>
    </nav>
</nav>
    </div>

    <!-- User Controls -->
    <div class="flex items-center space-x-4">
      <!-- Login/Logout Button -->
      <div class="relative dropdown">
        <button class="flex items-center space-x-2 focus:outline-none">
          <img class="h-8 w-8 rounded-full"
            src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
            alt="User avatar">
          <span class="hidden md:inline text-gray-600">Admin User</span>
          <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>

        <!-- Dropdown Menu -->
        <div class="dropdown-menu absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden">
          <a href="../admin/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Your
            Profile</a>
          <a href="../admin/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
          <div class="border-t border-gray-200"></div>
          <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</a>
        </div>
      </div>

      <!-- Mobile Menu Button -->
      <button class="md:hidden text-gray-500 hover:text-gray-600 focus:outline-none">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
    </div>
  </div>

  <!-- Mobile Navigation (hidden by default) -->
  <div class="md:hidden hidden" id="mobile-menu">
    <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
      <a href="#sales"
        class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-indigo-600">Sales Overview</a>
      <a href="#transactions"
        class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-indigo-600">Transactions</a>
      <a href="#products"
        class="block px-3 py-2 rounded-md text-base font-medium text-gray-600 hover:text-indigo-600">Products</a>
    </div>
  </div>
</header>

<script>
  // Toggle mobile menu
  document.querySelector('.md\\:hidden').addEventListener('click', function () {
    const menu = document.getElementById('mobile-menu');
    menu.classList.toggle('hidden');
  });

  // Close dropdown when clicking outside
  document.addEventListener('click', function (event) {
    const dropdowns = document.querySelectorAll('.dropdown');
    dropdowns.forEach(dropdown => {
      if (!dropdown.contains(event.target)) {
        dropdown.querySelector('.dropdown-menu').classList.add('hidden');
      }
    });
  });

  // Toggle dropdown menu
  document.querySelectorAll('.dropdown button').forEach(button => {
    button.addEventListener('click', function () {
      this.parentElement.querySelector('.dropdown-menu').classList.toggle('hidden');
    });
  });
</script>