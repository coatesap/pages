<?php

namespace Optimus\Pages\Http\Controllers;

use Illuminate\Http\Request;
use Optimus\Pages\Models\Page;
use Illuminate\Routing\Controller;
use Optimus\Pages\Jobs\UpdatePageUri;
use Optimus\Pages\Models\PageTemplate;
use Optimus\Pages\Http\Resources\PageResource;

class PagesController extends Controller
{
    /**
     * Display a list of pages.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $pages = Page::withDrafts()
            ->withCount('children')
            ->filter($request)
            ->orderBy('order')
            ->get();

        return PageResource::collection($pages);
    }

    /**
     * Create a new page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $this->validatePage($request);

        $template = PageTemplate::find($request->input('template_id'));

        $template->handler->validate($request);

        $page = Page::create([
            'title' => $request->input('title'),
            'slug' => $request->input('slug'),
            'parent_id' => $request->input('parent_id'),
            'template_id' => $template->id,
            'is_stand_alone' => $request->input('is_stand_alone'),
            'order' => Page::max('order') + 1
        ]);

        UpdatePageUri::dispatch($page);

        $page->setMeta('title', $request->input('meta.title'));
        $page->setMeta('description', $request->input('meta.description'));

        $template->handler->save($page, $request);

        if ($request->input('is_published')) {
            $page->publish();
        }

        return new PageResource($page);
    }

    /**
     * Display the specified page.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $page = Page::withDrafts()->findOrFail($id);

        return new PageResource($page);
    }

    /**
     * Update the specified page.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $page = Page::withDrafts()->findOrFail($id);

        $this->validatePage($request);

        $template = ! $page->has_fixed_template
            ? PageTemplate::find($request->input('template_id'))
            : $page->template;

        $template->handler->validate($request);

        $page->update([
            'title' => $request->input('title'),
            'slug' => ! $page->has_fixed_uri
                ? $request->input('slug')
                : $page->slug,
            'parent_id' => $request->input('parent_id'),
            'template_id' => $template->id,
            'is_stand_alone' => $request->input('is_stand_alone')
        ]);

        if (! $page->has_fixed_uri) {
            UpdatePageUri::dispatch($page);
        }

        $page->syncMeta([
            'title' => $request->input('meta.title'),
            'description' => $request->input('meta.description')
        ]);

        $page->detachMedia();
        $page->deleteContents();

        $template->handler->save($page, $request);

        if ($page->isDraft() && $request->input('is_published')) {
            $page->publish();
        } elseif ($page->isPublished() && ! $request->input('is_published')) {
            $page->draft();
        }

        return new PageResource($page);
    }

    /**
     * Reorder a specified list of pages.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function reorder(Request $request)
    {
        $request->validate([
            'pages' => 'required|array',
            'pages.*' => 'exists:pages,id'
        ]);

        $order = 1;

        foreach ($request->input('pages') as $id) {
            Page::where('id', $id)->update([
                'order' => $order
            ]);

            $order++;
        }

        return response(null, 204);
    }

    /**
     * Delete the specified page.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Page::withDrafts()
            ->where('is_deletable', true)
            ->findOrFail($id)
            ->delete();

        return response(null, 204);
    }

    /**
     * Validate the request.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    protected function validatePage(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'template_id' => 'required|exists:page_templates,id',
            'parent_id' => 'exists:pages,id|nullable',
            'is_stand_alone' => 'present|boolean',
            'is_published' => 'present|boolean'
        ]);
    }
}
