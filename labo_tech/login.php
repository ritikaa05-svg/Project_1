<?php
session_start();
if(isset($_SESSION['admin'])){
  header('location: admin/');
}else if(isset($_SESSION['employ'])){
  header('location: employ/');

}else if(isset($_SESSION['customer'])){
  header('location: customer');
}else if (isset($_SESSION['manager'])){
  header('location: manager/');
}
?>
<html>

<head>
  <title>
    Login page | Labo Test
  </title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="icon" type="image/webp" href="assets/logo_without_bg.png">

</head>

<body>



  <div class="flex min-h-full flex-col justify-center px-6 py-12 lg:px-8">
    <!-- Return to Home Button -->
    <div class="absolute top-4 left-4">
      <a href="index.html" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Return to Home
      </a>
    </div>
    
    <div class="sm:mx-auto sm:w-full sm:max-w-sm">
      <a href="index.html">
        <img class="mx-auto h-10 w-auto cursor-pointer" src="assets/logo_without_bg.png" alt="Your Company" style="
    height: 100px;
">
      </a>
      <h2 class="mt-10 text-center text-2xl/9 font-bold tracking-tight text-gray-900">Sign in to your account</h2>
    </div>

    <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
      <form class="space-y-6" action="actions/login" method="POST">
        <div>
          <label for="email" class="block text-sm/6 font-medium text-gray-900">Email address</label>
          <div class="mt-2">
            <input type="email" name="email" id="email" autocomplete="email" required=""
              class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
          </div>
        </div>

        <div>
          <div class="flex items-center justify-between">
            <label for="password" class="block text-sm/6 font-medium text-gray-900">Password</label>
            <div class="text-sm">
              <a href="#" class="font-semibold text-indigo-600 hover:text-indigo-500">Forgot password?</a>
            </div>
          </div>
          <div class="mt-2">
            <input type="password" name="password" id="password" autocomplete="current-password" required=""
              class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 outline outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
          </div>
        </div>

        <div style="color: red;">
          <button type="submit"
            class="flex w-full justify-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            style="
    background: #ba0f0f;
">Sign
            in</button>
        </div>
      </form>

      <p class="mt-10 text-center text-sm/6 text-gray-500">
        <?php
        if(isset($_SESSION['error'])){
          echo $_SESSION['error'] ;
        unset($_SESSION['error']);
        }
          ?>
      </p>

      <div class="mt-6 text-center">
        <p class="text-sm text-gray-600">
          Don't have an account? 
          <a href="register" class="font-semibold text-indigo-600 hover:text-indigo-500">
            Sign up here
          </a>
        </p>
      </div>
    </div>
  </div>





</body>

</html>