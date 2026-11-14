<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    /** Admin only (enforced by the route group). */
    public function index()
    {
        return ContactResource::collection(Contact::latest('id')->paginate(10));
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        Contact::create($request->validated());

        return response()->json(['message' => __('messages.newContact')], 201);
    }

    public function destroy(Contact $contact): JsonResponse
    {
        $contact->delete();

        return response()->json(['message' => __('messages.deletedContact')]);
    }
}
