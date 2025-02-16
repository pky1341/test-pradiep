<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Sent - Contact Us</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100 font-sans">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-lg overflow-hidden shadow-lg">
            <div class="py-4 px-6 bg-green-500 text-white">
                <h2 class="text-2xl font-bold">Success!</h2>
            </div>

            <div class="py-4 px-6">
                <div class="mb-4 text-center">
                    <svg class="h-16 w-16 text-green-500 mx-auto my-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>

                    <h3 class="text-xl font-bold text-gray-800 mb-2">Thank You!</h3>

                    @if (session('success'))
                    <p class="text-gray-600">{{ session('success') }}</p>
                    @else
                    <p class="text-gray-600">Your message has been sent successfully.</p>
                    @endif
                </div>

                <div class="mt-8 text-center">
                    <a href="{{ route('contact.form') }}"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Back to Contact Form
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>