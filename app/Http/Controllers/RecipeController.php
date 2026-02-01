<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\RecipeStep;
use App\Models\Ingredient;
use App\Models\RecipeExecution;
use Illuminate\Support\Facades\DB;

class RecipeController extends Controller
{
    // Helper method to check recipe permissions
    private function checkRecipeAccess()
    {
        if (!auth()->user()->can_view_recipes && auth()->user()->email !== 'proximalumine@gmail.com') {
            abort(403, 'Brak dostępu do receptur');
        }
    }

    // KATALOG SKŁADNIKÓW
    public function ingredientsIndex()
    {
        $this->checkRecipeAccess();
        $ingredients = Ingredient::orderBy('name')->get();
        return view('recipes.ingredients', compact('ingredients'));
    }

    public function ingredientStore(Request $request)
    {
        $this->checkRecipeAccess();
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'quantity' => 'required|numeric|min:0',
            'price' => 'nullable|numeric|min:0'
        ]);

        Ingredient::create($request->all());
        return redirect()->route('recipes.ingredients')->with('success', 'Składnik został dodany!');
    }

    public function ingredientUpdate(Request $request, Ingredient $ingredient)
    {
        $this->checkRecipeAccess();
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|string|max:50',
            'quantity' => 'required|numeric|min:0',
            'price' => 'nullable|numeric|min:0'
        ]);

        $ingredient->update($request->all());
        return redirect()->route('recipes.ingredients')->with('success', 'Składnik został zaktualizowany!');
    }

    public function ingredientDestroy(Ingredient $ingredient)
    {
        $this->checkRecipeAccess();
        $ingredient->delete();
        return redirect()->route('recipes.ingredients')->with('success', 'Składnik został usunięty!');
    }

    // LISTA RECEPTUR
    public function index()
    {
        $this->checkRecipeAccess();
        $recipes = Recipe::withCount('steps')->orderBy('name')->get();
        return view('recipes.index', compact('recipes'));
    }

    // TWORZENIE RECEPTURY
    public function create()
    {
        $this->checkRecipeAccess();
        $ingredients = Ingredient::orderBy('name')->get();
        return view('recipes.create', compact('ingredients'));
    }

    public function store(Request $request)
    {
        $this->checkRecipeAccess();
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'output_quantity' => 'required|integer|min:1',
            'flour' => 'required|array|min:1',
            'flour.*.ingredient_id' => 'required|exists:ingredients,id',
            'flour.*.weight' => 'required|numeric|min:0.01',
            'flour.*.percentage' => 'required|numeric|min:0.01|max:100',
        ]);

        DB::beginTransaction();
        try {
            // Sprawdź czy suma procentów mąki = 100%
            $flourPercentageSum = collect($request->flour)->sum('percentage');
            if (abs($flourPercentageSum - 100) > 0.01) {
                return back()->with('error', 'Suma procentów mąki musi wynosić 100%!');
            }
            
            $recipe = Recipe::create([
                'name' => $request->name,
                'description' => $request->description,
                'output_quantity' => $request->output_quantity,
                'total_steps' => 0,
                'estimated_time' => 0
            ]);

            $order = 1;
            
            // Dodaj mąkę
            foreach ($request->flour as $flour) {
                RecipeStep::create([
                    'recipe_id' => $recipe->id,
                    'order' => $order++,
                    'type' => 'ingredient',
                    'ingredient_id' => $flour['ingredient_id'],
                    'quantity' => $flour['weight'],
                    'percentage' => $flour['percentage'],
                    'is_flour' => true,
                ]);
            }
            
            // Dodaj pozostałe składniki
            if ($request->has('ingredient')) {
                foreach ($request->ingredient as $ingredient) {
                    RecipeStep::create([
                        'recipe_id' => $recipe->id,
                        'order' => $order++,
                        'type' => 'ingredient',
                        'ingredient_id' => $ingredient['ingredient_id'],
                        'quantity' => $ingredient['quantity'],
                        'percentage' => $ingredient['percentage'],
                        'is_flour' => false,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('recipes.index')->with('success', 'Receptura została utworzona!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Błąd podczas tworzenia receptury: ' . $e->getMessage());
        }
    }

    // EDYCJA RECEPTURY
    public function edit(Recipe $recipe)
    {
        $this->checkRecipeAccess();
        $recipe->load('steps.ingredient');
        $ingredients = Ingredient::orderBy('name')->get();
        return view('recipes.edit', compact('recipe', 'ingredients'));
    }

    public function update(Request $request, Recipe $recipe)
    {
        $this->checkRecipeAccess();
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'output_quantity' => 'required|integer|min:1',
            'steps' => 'required|array|min:1',
        ]);

        DB::beginTransaction();
        try {
            $recipe->update([
                'name' => $request->name,
                'description' => $request->description,
                'output_quantity' => $request->output_quantity,
                'total_steps' => count($request->steps),
                'estimated_time' => $this->calculateEstimatedTime($request->steps)
            ]);

            // Usuń stare kroki i utwórz nowe
            $recipe->steps()->delete();
            
            foreach ($request->steps as $index => $step) {
                RecipeStep::create([
                    'recipe_id' => $recipe->id,
                    'order' => $index + 1,
                    'type' => $step['type'],
                    'action_name' => $step['action_name'] ?? null,
                    'action_description' => $step['action_description'] ?? null,
                    'duration' => $step['duration'] ?? null,
                    'ingredient_id' => $step['ingredient_id'] ?? null,
                    'quantity' => $step['quantity'] ?? null,
                ]);
            }

            DB::commit();
            return redirect()->route('recipes.index')->with('success', 'Receptura została zaktualizowana!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Błąd podczas aktualizacji: ' . $e->getMessage());
        }
    }

    public function destroy(Recipe $recipe)
    {
        $this->checkRecipeAccess();
        $recipe->delete();
        return redirect()->route('recipes.index')->with('success', 'Receptura została usunięta!');
    }

    // SKALOWANIE RECEPTURY
    public function scale(Recipe $recipe)
    {
        $this->checkRecipeAccess();
        $recipe->load('steps.ingredient');
        return view('recipes.scale', compact('recipe'));
    }

    public function processScale(Request $request, Recipe $recipe)
    {
        $this->checkRecipeAccess();
        $request->validate([
            'desired_quantity' => 'required|integer|min:1',
        ]);

        $recipe->load('steps.ingredient');
        
        // Oblicz współczynnik skalowania
        $scaleFactor = $request->desired_quantity / $recipe->output_quantity;
        
        // Przeskaluj wszystkie składniki
        $scaledSteps = $recipe->steps->map(function($step) use ($scaleFactor) {
            $scaledStep = $step->toArray();
            $scaledStep['scaled_quantity'] = $step->quantity * $scaleFactor;
            $scaledStep['ingredient'] = $step->ingredient;
            return (object)$scaledStep;
        });
        
        return view('recipes.scale', [
            'recipe' => $recipe,
            'scaledSteps' => $scaledSteps,
            'desiredQuantity' => $request->desired_quantity,
            'scaleFactor' => $scaleFactor
        ]);
    }

    // ROZPOCZĘCIE REALIZACJI
    public function startExecution(Recipe $recipe)
    {
        $this->checkRecipeAccess();
        // Sprawdź dostępność składników
        $missingIngredients = [];
        foreach ($recipe->steps()->where('type', 'ingredient')->with('ingredient')->get() as $step) {
            if ($step->ingredient && $step->ingredient->quantity < $step->quantity) {
                $missingIngredients[] = $step->ingredient->name . ' (potrzeba: ' . $step->quantity . ' ' . $step->ingredient->unit . ', dostępne: ' . $step->ingredient->quantity . ' ' . $step->ingredient->unit . ')';
            }
        }

        if (!empty($missingIngredients)) {
            return back()->with('error', 'Brak wystarczających składników: ' . implode(', ', $missingIngredients));
        }

        DB::beginTransaction();
        try {
            // Odejmij składniki z magazynu
            foreach ($recipe->steps()->where('type', 'ingredient')->with('ingredient')->get() as $step) {
                if ($step->ingredient) {
                    $step->ingredient->decrement('quantity', $step->quantity);
                }
            }

            // Utwórz realizację
            $execution = RecipeExecution::create([
                'recipe_id' => $recipe->id,
                'user_id' => auth()->id(),
                'current_step' => 1,
                'status' => 'in_progress',
                'started_at' => now(),
                'step_completions' => []
            ]);

            DB::commit();
            return redirect()->route('recipes.execute', $execution)->with('success', 'Rozpoczęto realizację receptury!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Błąd podczas rozpoczynania realizacji: ' . $e->getMessage());
        }
    }

    // WIDOK REALIZACJI
    public function execute(RecipeExecution $execution)
    {
        $this->checkRecipeAccess();
        $execution->load('recipe.steps.ingredient');
        return view('recipes.execute', compact('execution'));
    }

    // POTWIERDZENIE KROKU
    public function confirmStep(Request $request, RecipeExecution $execution)
    {
        $this->checkRecipeAccess();
        $stepNumber = $request->step_number;
        
        $completions = $execution->step_completions ?? [];
        $completions[] = [
            'step' => $stepNumber,
            'completed_at' => now()->toDateTimeString()
        ];

        $execution->update([
            'step_completions' => $completions,
            'current_step' => $stepNumber + 1
        ]);

        // Sprawdź czy to był ostatni krok
        if ($stepNumber >= $execution->recipe->total_steps) {
            $execution->update([
                'status' => 'completed',
                'completed_at' => now()
            ]);
            return response()->json(['status' => 'completed', 'message' => 'Receptura zakończona!']);
        }

        return response()->json(['status' => 'next', 'next_step' => $stepNumber + 1]);
    }

    // Pomocnicza metoda do obliczania szacowanego czasu
    private function calculateEstimatedTime($steps)
    {
        $totalSeconds = 0;
        foreach ($steps as $step) {
            if ($step['type'] === 'action' && isset($step['duration'])) {
                $totalSeconds += (int)$step['duration'];
            }
        }
        return ceil($totalSeconds / 60); // zwróć w minutach
    }
}
