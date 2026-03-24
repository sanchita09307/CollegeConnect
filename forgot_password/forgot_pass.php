<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Forgot Password - CollegeConnect</title>
<!-- Tailwind CSS with Plugins -->
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<!-- Google Fonts: Lexend and Noto Sans -->
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&amp;family=Noto+Sans:wght@400;700&amp;display=swap" rel="stylesheet"/>
<!-- Material Symbols -->
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&amp;display=swap" rel="stylesheet"/>
<script id="tailwind-config">
        tailwind.config = {
          darkMode: "class",
          theme: {
            extend: {
              colors: {
                "primary": "#2630ed",
                "background-light": "#f6f6f8",
                "background-dark": "#101122",
              },
              fontFamily: {
                "display": ["Lexend", "sans-serif"]
              },
              borderRadius: {"DEFAULT": "0.25rem", "lg": "0.5rem", "xl": "0.75rem", "full": "9999px"},
            },
          },
        }
    </script>
<style>
    body {
      min-height: max(884px, 100dvh);
    }
  </style>
  </head>
<body class="bg-background-light dark:bg-background-dark font-display text-[#111118] dark:text-white transition-colors duration-200">
<div class="relative flex h-screen min-h-screen w-full flex-col overflow-x-hidden">
<!-- Top Navigation Bar -->
<div class="flex items-center bg-transparent p-4 pb-2 justify-between">
<a class="text-[#111118] dark:text-white flex size-12 shrink-0 items-center justify-start"
   data-icon="ArrowLeft"
   href="../login.php">
  <span class="material-symbols-outlined text-[24px]">arrow_back_ios</span>
</a>

<h2 class="text-[#111118] dark:text-white text-lg font-bold leading-tight tracking-[-0.015em] flex-1 text-center pr-12">CollegeConnect</h2>
</div>
<div class="flex-1 flex flex-col justify-center px-4 max-w-[480px] mx-auto w-full">
<!-- Branding/Image Section -->
<div class="@container">
<div class="@[480px]:px-4 @[480px]:py-3">
<div class="w-24 h-24 mx-auto mb-6 bg-primary/10 rounded-full flex items-center justify-center">
<span class="material-symbols-outlined text-primary text-[48px]">lock_reset</span>
</div>
<!-- Decorative background style if needed -->
<div class="hidden w-full bg-center bg-no-repeat bg-cover flex flex-col justify-end overflow-hidden bg-white @[480px]:rounded-lg min-h-[100px]" data-alt="Abstract academic blue gradient background" style="background-image: linear-gradient(135deg, #2630ed 0%, #6e75f3 100%);"></div>
</div>
</div>
<!-- Title & Description -->
<h1 class="text-[#111118] dark:text-white tracking-light text-[32px] font-bold leading-tight text-center pb-3 pt-6">Forgot Password?</h1>
<p class="text-[#111118]/70 dark:text-white/70 text-base font-normal leading-normal pb-8 pt-1 px-4 text-center">
                No worries! Enter your registered email or University ID below and we'll send you instructions to reset your password.
            </p>
<!-- Recovery Form -->
<div class="flex flex-col gap-6 w-full">
<div class="flex flex-wrap items-end gap-4">
<label class="flex flex-col min-w-40 flex-1">
<p class="text-[#111118] dark:text-white text-sm font-semibold leading-normal pb-2 ml-1">Email ID</p>
<div class="relative flex items-center">
<span class="material-symbols-outlined absolute left-4 text-[#616389]">person</span>
<input id="email"
  class="form-input flex w-full min-w-0 flex-1 resize-none overflow-hidden rounded-xl text-[#111118] dark:text-white focus:outline-0 focus:ring-2 focus:ring-primary/50 border border-[#dbdce6] dark:border-white/20 bg-white dark:bg-white/5 focus:border-primary h-14 placeholder:text-[#616389] pl-12 pr-4 text-base font-normal leading-normal transition-all"
  placeholder="e.g., student_id@college.edu or 123456"
  type="text">
</div>
</label>
</div>
<!-- Primary Action Button -->
<div class="px-0 py-3">
<button onclick="sendResetLink()"
  class="w-full bg-primary hover:bg-primary/90 text-white font-bold py-4
  rounded-xl text-lg shadow-lg shadow-primary/20 transition-all
  flex items-center justify-center gap-2">
  <span>Send Reset Link</span>
  <span class="material-symbols-outlined">send</span>
</button>

</div>
</div>
<!-- Back to Login -->
<div class="mt-8 text-center pb-10">
<p class="text-[#111118]/60 dark:text-white/60 text-base">
                    Remember your password? 
                   <a href="../login.php"class="text-primary text-sm font-medium hover:underline">
     Back to login
  </a>



</p>
</div>
</div>
<!-- Safe area footer spacer for iOS -->
<div class="h-10 bg-transparent"></div>
</div>
<script>
function sendResetLink() {
  const email = document.getElementById("email").value.trim();

  if (email === "") {
    alert("Please enter your Email ID or University ID");
    return;
  }

  // Redirect to Reset Password page
  window.location.href = "../forgot_password/reset_pass.php";
}
</script>


</body></html>