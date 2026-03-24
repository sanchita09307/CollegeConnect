<!DOCTYPE html>

<html class="light" lang="en"><head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>Reset Password - CollegeConnect</title>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet"/>
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
                    borderRadius: {
                        "DEFAULT": "0.25rem",
                        "lg": "0.5rem",
                        "xl": "0.75rem",
                        "full": "9999px"
                    },
                },
            },
        }
    </script>
<style>
        body {
            font-family: 'Lexend', sans-serif;
        }
        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }
    </style>
<style>
    body {
      min-height: max(884px, 100dvh);
    }
  </style>
  </head>
<body class="bg-background-light dark:bg-background-dark font-display text-[#111118] dark:text-white transition-colors duration-200">
<div class="relative flex h-screen min-h-screen w-full flex-col overflow-x-hidden">

<!-- TopAppBar Component -->
<div class="flex items-center bg-transparent p-4 pb-2 justify-between">
<a class="text-[#111118] dark:text-white flex size-12 shrink-0 items-center justify-start"
   data-icon="ArrowLeft"
   href="../login.php">
  <span class="material-symbols-outlined text-[24px]">arrow_back_ios</span>
</a>

<h2 class="text-[#111118] dark:text-white text-lg font-bold leading-tight tracking-[-0.015em] flex-1 text-center pr-12">CollegeConnect</h2>
</div>
<!-- HeadlineText Component -->
<div class="px-4">
<h1 class="text-[#111118] dark:text-white tracking-light text-[32px] font-bold leading-tight pt-8 pb-2">Create new password</h1>
<!-- BodyText Component -->
<p class="text-[#616389] dark:text-gray-400 text-base font-normal leading-normal pb-6">
                Choose a strong password you haven't used before for CollegeConnect.
            </p>
</div>
<!-- Form Section -->
<div class="flex flex-col gap-2">
<!-- TextField Component: New Password -->
<div class="flex flex-wrap items-end gap-4 px-4 py-3">
<label class="flex flex-col min-w-40 flex-1">
<p class="text-[#111118] dark:text-gray-200 text-sm font-medium leading-normal pb-2">New Password</p>
<div class="flex w-full flex-1 items-stretch rounded-lg shadow-sm">
<input
  id="newPassword"
  type="password"
  placeholder="••••••••"
  class="form-input flex w-full ..."
/>
<div class="text-[#616389] dark:text-gray-400 flex border border-[#dbdce6] dark:border-gray-700 bg-white dark:bg-gray-800 items-center justify-center pr-[15px] rounded-r-lg border-l-0">
<span class="material-symbols-outlined cursor-pointer">visibility</span>
</div>
</div>
</label>
</div>
<!-- ProgressBar Component: Strength -->
<!-- Password Strength (WORKING) -->
<div class="flex flex-col gap-3 px-4 py-2">
  <div class="flex justify-between">
    <p class="text-sm font-medium">Password strength</p>
    <p id="strengthText" class="text-primary text-sm font-bold">0 / 100</p>
  </div>

  <div class="rounded-full bg-[#dbdce6] h-2 overflow-hidden">
    <div id="strengthBar" class="h-2 rounded-full bg-primary" style="width:0%"></div>
  </div>

  <p id="strengthMsg" class="text-xs text-gray-500">
    Start typing a password
  </p>
</div>


<!-- TextField Component: Confirm Password -->
<div class="flex flex-wrap items-end gap-4 px-4 py-3">
<label class="flex flex-col min-w-40 flex-1">
<p class="text-[#111118] dark:text-gray-200 text-sm font-medium leading-normal pb-2">Confirm New Password</p>
<div class="flex w-full flex-1 items-stretch rounded-lg shadow-sm">
<input
  id="newPassword"
  type="password"
  placeholder="••••••••"
  class="form-input flex w-full ..."
/>
<div class="text-[#616389] dark:text-gray-400 flex border border-[#dbdce6] dark:border-gray-700 bg-white dark:bg-gray-800 items-center justify-center pr-[15px] rounded-r-lg border-l-0">
<span class="material-symbols-outlined cursor-pointer">visibility_off</span>
</div>
</div>
</label>
</div>
</div>
<!-- Security Tips Section -->
<div class="m-4 p-4 bg-primary/5 dark:bg-primary/10 rounded-xl border border-primary/10">
<h3 class="text-[#111118] dark:text-white text-sm font-bold flex items-center gap-2 mb-3">
<span class="material-symbols-outlined text-primary text-lg">verified_user</span>
                Security Tips
            </h3>
<ul class="space-y-2">
<li class="flex items-start gap-2 text-xs text-[#616389] dark:text-gray-400">
<span class="material-symbols-outlined text-green-500 text-[14px] mt-0.5">check</span>
                    Use at least 8 characters
                </li>
<li class="flex items-start gap-2 text-xs text-[#616389] dark:text-gray-400">
<span class="material-symbols-outlined text-green-500 text-[14px] mt-0.5">check</span>
                    Include at least one uppercase letter
                </li>
<li class="flex items-start gap-2 text-xs text-[#616389] dark:text-gray-400">
<span class="material-symbols-outlined text-green-500 text-[14px] mt-0.5">check</span>
                    Include a number or special character
                </li>
<li class="flex items-start gap-2 text-xs text-[#616389] dark:text-gray-400">
<span class="material-symbols-outlined text-gray-300 text-[14px] mt-0.5">radio_button_unchecked</span>
                    Avoid using your name or date of birth
                </li>
</ul>
</div>
<!-- Spacer -->
<div class="flex-grow"></div>
<!-- Action Button -->
<div class="p-4 pb-10">
<button class="w-full bg-primary text-white font-bold py-4 rounded-xl shadow-lg shadow-primary/30 active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                Reset Password
                <span class="material-symbols-outlined">lock_reset</span>
</button>
<button class="w-full mt-3 bg-transparent text-[#616389] dark:text-gray-400 font-medium py-2 rounded-lg text-sm">
                Cancel
            </button>
</div>
<!-- iOS Home Indicator -->
<div class="absolute bottom-1 left-1/2 -translate-x-1/2 w-32 h-1 bg-gray-300 dark:bg-gray-700 rounded-full"></div>
</div>
<script>
const newPassword = document.getElementById("newPassword");
const strengthBar = document.getElementById("strengthBar");
const strengthText = document.getElementById("strengthText");
const strengthMsg = document.getElementById("strengthMsg");

newPassword.addEventListener("input", () => {
  let pwd = newPassword.value;
  let score = 0;

  if (pwd.length >= 8) score += 25;
  if (/[A-Z]/.test(pwd)) score += 25;
  if (/[0-9!@#$%^&*]/.test(pwd)) score += 25;
  if (!/(123|password)/i.test(pwd)) score += 25;

  strengthBar.style.width = score + "%";
  strengthText.innerText = score + " / 100";

  if (score < 50) strengthMsg.innerText = "Weak password";
  else if (score < 75) strengthMsg.innerText = "Good password";
  else strengthMsg.innerText = "Strong password 🔒";
});
</script>

</body></html>