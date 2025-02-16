<?php

namespace App\Http\Controllers;

use App\Events\ContactForm;
use App\Models\User;
use Illuminate\Http\Request;

class ContactFormController extends Controller
{
    /**
     * Show the contact form.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('contact.form');
    }

    /**
     * Store a new contact form submission.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string',
        ]);

        try {
            $contactForm = User::create($validated);
            event(new ContactForm($contactForm));

            return redirect()->route('contact.success')
                ->with('success', 'Your message has been received. We will get back to you soon!');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors([
                'error' => 'There was a problem submitting your form. Please try again later.',
            ]);
        }
    }

    /**
     * Show the success page.
     *
     * @return \Illuminate\View\View
     */
    public function success()
    {
        return view('contact.success');
    }
}
