<?php

namespace App\Http\Controllers;
use App\Transformers\CategoryTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use Illuminate\Support\Facades\Auth;


use App\Language;
use App\Category;

use Validator;

class CategoryController extends Controller
{
    private $fractal;

    /**
     * @var UserTransformer
     */
    private $categoryTransformer;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Manager $fractal, CategoryTransformer $categoryTransformer)
    {
        $this->fractal = $fractal;
        $this->categoryTransformer = $categoryTransformer;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $loginType = session()->get('login_type');

        if($loginType == ADMIN){
            $categories = Category::all();
        }else{
            $categories = Category::with(['language' => function($q){
                $q->where('language_id',Auth::user()->language_id);
                $q->select('category_language.name');
            }])->where('status' , CATEGORY_ACTIVE)->get();
            $categories = new Collection($categories, $this->categoryTransformer);


            $categories = $this->fractal->createData($categories); // Transform data
            return response() ->json(['status' => true , 'result' => $categories->toArray()]);
        }


        return view('admin.category.index')
            ->with([
                'categories' => $categories,
            ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        $languages = Language::all();

        return view('admin.category.create')->with([
            'languages' => $languages,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $data = $request->all();
//dd($request->all());
        Validator::make(
            $data,
            [
                'status' => 'sometimes|required|integer|in:' . CATEGORY_ACTIVE,
                'category_name' => 'required|unique:categories,name|max:190',
                'language' => 'required|array',
            ]
        )->validate();

        //dd($request->all());

        DB::beginTransaction();
        $category = new Category();

        $category->name = e(trim($request->category_name));

        if($request->has('status'))
            $category->status = CATEGORY_ACTIVE;

        // get all languages
        $languages = Language::pluck('id');

        $typedLanguages = $request->language;

        //dd($languages);
        if($category->save()) {
            $categoryLanguages = [];
            foreach ($languages as $language) {
                if (isset($typedLanguages[$language]))
                    $categoryLanguages[$language]['name'] = e(trim($typedLanguages[$language]));

            }
//dd($categoryLanguages);
            $category->language()->attach($categoryLanguages);

            DB::commit();
            return redirect('/admin/categories')->with([
                'messageSuccess' => __("general.categoryAddedSuccessfully")
            ]);
        }else{
            DB::rollBack();
            return redirect()->back()->with([
                'messageDander' => __("general.errorAddingCategory")
            ]);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        $category = Category::with(['language'])->where('id',$id)->first();

//dd($categoryLanguages->toArray());
        if($category){
            $categoryLanguages = $category->language->mapWithKeys(function ($item) {
                return [$item['id'] => $item];
            });

            return view('admin.category.edit')->with([
                'category' => $category,
                'categoryLanguages' => $categoryLanguages,
                'languages' => Language::all()
            ]);

        }else
            return redirect('/admin/categories')->with(['messageDanger' => __('general.categoryNotFound') ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $data = $request->all();

        Validator::make(
            $data,
            [
                'category_id' => 'required|integer|exists:categories,id',
                'status' => 'sometimes|required|integer|in:' . CATEGORY_ACTIVE,
                'category_name' => ['required','max:190' , Rule::unique('categories','name')->ignore($id)],
                'language' => 'required|array',
            ]
        )->validate();

        //dd($request->all());

        DB::beginTransaction();
        $category = Category::find($data['category_id']);

        if($request->has('status'))
            $category->status = CATEGORY_ACTIVE;
        else
            $category->status = CATEGORY_INACTIVE;

        $category->name = e(trim($request->category_name));

        // get all languages
        $languages = Language::pluck('id');

        $typedLanguages = $request->language;

        //dd($languages);
        if($category->save()) {
            // Delete old relation


            $categoryLanguages = [];
            foreach ($languages as $language) {
                if (isset($typedLanguages[$language]))
                    $categoryLanguages[$language]['name'] = e(trim($typedLanguages[$language]));

            }

            $category->language()->detach();

            $category->language()->attach($categoryLanguages);

            DB::commit();
            return redirect('/admin/categories')->with([
                'messageSuccess' => __("general.categoryUpdatedSuccessfully")
            ]);
        }else{
            DB::rollBack();
            return redirect()->back()->with([
                'messageDander' => __("general.errorUpdateCategory")
            ]);
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
