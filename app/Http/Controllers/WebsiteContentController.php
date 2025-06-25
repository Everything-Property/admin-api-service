<?php

namespace App\Http\Controllers;

use App\Models\WebsiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class WebsiteContentController extends Controller
{
    // Get all content entries
    public function index()
{
    // Exclude records with IDs 1 and 2
    $websiteContents = WebsiteContent::whereNotIn('id', [1, 2])->get();

    return response()->json($websiteContents, 200);
}
    
    

    // Get a single content entry by ID
    public function show($id)
    {
        $content = WebsiteContent::find($id);
        if (!$content) {
            return response()->json(['message' => 'Content not found'], 404);
        }
        return response()->json($content, 200);
    }

    // Create a new content entry
    public function store(Request $request)
    {
    $validator = Validator::make($request->all(), [
        'title' => 'required|string',
        'content' => 'required|string',
        'cover_image' => 'nullable|image|max:2048', // Validate the image
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    // Create the content_key based on the title
    $data = $request->all();
    $data['content_key'] = Str::slug($data['title']); // Generate slug from title

    // Ensure the generated content_key is unique in the database
    $existingContentKey = WebsiteContent::where('content_key', $data['content_key'])->exists();
    if ($existingContentKey) {
        return response()->json(['error' => 'Generated content key already exists. Please use a different title.'], 409);
    }

    // Handle file upload if provided
    if ($request->hasFile('cover_image')) {
        $data['cover_image'] = $request->file('cover_image')->store('cover_images', 'public');
    }

    $content = WebsiteContent::create($data);
    return response()->json($content, 201);
}





public function update(Request $request, $id)
{
    // Find the content by ID
    $content = WebsiteContent::find($id);
    if (!$content) {
        Log::warning('Content Not Found', ['id' => $id]);
        return response()->json(['message' => 'Content not found'], 404);
    }

    // Base validation rules (cover_image not included yet)
    $rules = [
        'title' => 'sometimes|string',
        'content' => 'sometimes|string',
    ];

    // Add cover_image rules only if a file is uploaded
    if ($request->hasFile('cover_image')) {
        $rules['cover_image'] = 'file|mimes:jpeg,png,jpg,gif|max:2048';
    }

    // Validate the request
    $validator = Validator::make($request->all(), $rules);

    if ($validator->fails()) {
        Log::error('Validation Failed', ['errors' => $validator->errors()]);
        return response()->json($validator->errors(), 422);
    }

    Log::info('Validation Passed');

    $data = $request->all();

    // Check if title is provided and generate new content_key
    if (isset($data['title'])) {
        $data['content_key'] = Str::slug($data['title']);
        Log::info('Generated Content Key', ['content_key' => $data['content_key']]);

        $existingContentKey = WebsiteContent::where('content_key', $data['content_key'])
                                            ->where('id', '!=', $id)
                                            ->exists();
        if ($existingContentKey) {
            Log::warning('Content Key Conflict', ['content_key' => $data['content_key']]);
            return response()->json(['error' => 'Generated content key already exists. Please use a different title.'], 409);
        }
    }

    // Handle cover_image only if it’s a valid file
    if ($request->hasFile('cover_image') && $request->file('cover_image')->isValid()) {
        Log::info('Cover Image Provided', ['original_name' => $request->file('cover_image')->getClientOriginalName()]);

        // Delete the old cover image if it exists
        if ($content->cover_image) {
            Storage::disk('public')->delete($content->cover_image);
            Log::info('Old Cover Image Deleted', ['cover_image' => $content->cover_image]);
        }

        // Store the new image
        $data['cover_image'] = $request->file('cover_image')->store('cover_images', 'public');
        Log::info('New Cover Image Stored', ['cover_image' => $data['cover_image']]);
    } else {
        // Remove cover_image from $data to preserve existing value
        unset($data['cover_image']);
        Log::info('No Valid Cover Image Provided', ['cover_image_input' => $request->input('cover_image')]);
    }

    // Update the content
    Log::info('Updating Content', ['update_data' => $data]);
    $content->update($data);

    // Fetch the updated content
    $content->refresh();
    Log::info('Content Updated Successfully', ['updated_content' => $content]);

    return response()->json($content, 200);
}


    // Delete a content entry by ID
    public function destroy($id)
    {
        $content = WebsiteContent::find($id);
        if (!$content) {
            return response()->json(['message' => 'Content not found'], 404);
        }

        $content->delete();
        return response()->json(['message' => 'Content deleted successfully'], 200);
    }
}
