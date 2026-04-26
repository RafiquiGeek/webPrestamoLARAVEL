<?php

namespace App\Livewire\Clientes;

use App\Models\Cliente;
use App\Models\Persona;
use Livewire\Component;
use Livewire\WithPagination;

class ShowClientes extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $search;

    public $estadoPrestamo = '';

    public $sort = 'id';

    public $direction = 'desc';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedEstadoPrestamo()
    {
        $this->resetPage();
    }

    public function render()
    {
        // Obtener todas las personas con información de cliente y préstamos
        // Using subquery to get sucursal from first direccion to avoid duplicates
        $personas = Persona::select([
            'personas.*',
            'clientes.id as cliente_id',
            \DB::raw('(SELECT sucursales.sucursal FROM direcciones 
                      INNER JOIN sucursales ON direcciones.sucursal_id = sucursales.id 
                      WHERE direcciones.persona_id = personas.id 
                      LIMIT 1) as sucursal_nombre'),
        ])
            ->leftJoin('clientes', 'personas.id', '=', 'clientes.persona_id')
            ->with([
                'cliente.prestamos' => function ($query) {
                    $query->with(['convenios' => function ($q) {
                            $q->where('estado', 'Activo');
                        }])
                        ->orderBy('created_at', 'desc');
                },
                'cliente.usuario',
            ])
            ->when($this->search, function ($query) {
                $search = $this->sanitizeSearch($this->search);

                $query->where(function ($subQuery) use ($search) {
                    // 1. Búsqueda exacta de DNI (prioridad máxima)
                    $subQuery->where('personas.documento', 'like', '%'.$search.'%')

                        // 2. Búsqueda exacta del nombre completo
                        ->orWhereRaw("CONCAT(personas.nombres, ' ', personas.ape_pat, ' ', personas.ape_mat) like ?", ['%'.$search.'%'])

                        // 3. Búsqueda por palabras individuales (nombres, apellidos)
                        ->orWhere(function ($wordQuery) use ($search) {
                            // Dividir búsqueda en palabras
                            $words = explode(' ', $search);
                            foreach ($words as $word) {
                                if (strlen($word) >= 2) { // Solo palabras de 2+ caracteres
                                    $wordQuery->orWhere('personas.nombres', 'like', '%'.$word.'%')
                                        ->orWhere('personas.ape_pat', 'like', '%'.$word.'%')
                                        ->orWhere('personas.ape_mat', 'like', '%'.$word.'%');
                                }
                            }
                        })

                        // 4. Búsqueda tolerante a errores (SOUNDEX para español)
                        ->orWhere(function ($soundexQuery) use ($search) {
                            // Solo aplicar SOUNDEX si tiene más de 3 caracteres
                            if (strlen($search) > 3 && !is_numeric($search)) {
                                $soundexQuery->whereRaw("SOUNDEX(personas.nombres) = SOUNDEX(?)", [$search])
                                    ->orWhereRaw("SOUNDEX(personas.ape_pat) = SOUNDEX(?)", [$search])
                                    ->orWhereRaw("SOUNDEX(personas.ape_mat) = SOUNDEX(?)", [$search]);
                            }
                        });
                });
            })
            // Ordenar por relevancia cuando hay búsqueda
            ->when($this->search, function ($query) {
                $search = $this->sanitizeSearch($this->search);
                $escapedSearch = \DB::connection()->getPdo()->quote($search);

                // Dividir en palabras para búsqueda multi-palabra
                $words = array_filter(explode(' ', $search), function($w) { return strlen($w) >= 2; });
                $wordCount = count($words);

                // Construir condiciones para contar palabras encontradas
                $wordMatchConditions = [];
                foreach ($words as $word) {
                    $escapedWord = \DB::connection()->getPdo()->quote($word);
                    $wordMatchConditions[] = "
                        (CASE
                            WHEN CONCAT(personas.nombres, ' ', personas.ape_pat, ' ', personas.ape_mat) LIKE CONCAT('%', {$escapedWord}, '%') THEN 1
                            ELSE 0
                        END)
                    ";
                }
                $wordMatchSum = !empty($wordMatchConditions) ? implode(' + ', $wordMatchConditions) : '0';

                // Agregar score de relevancia mejorado
                $query->addSelect(\DB::raw("
                    (CASE
                        -- DNI exacto (máxima prioridad)
                        WHEN personas.documento = {$escapedSearch} THEN 1000

                        -- DNI comienza con búsqueda
                        WHEN personas.documento LIKE CONCAT({$escapedSearch}, '%') THEN 900

                        -- Nombre completo exacto (todas las palabras en orden)
                        WHEN CONCAT(personas.nombres, ' ', personas.ape_pat, ' ', personas.ape_mat) LIKE CONCAT('%', {$escapedSearch}, '%') THEN 800

                        -- Todas las palabras de búsqueda están presentes (cualquier orden)
                        WHEN ({$wordMatchSum}) >= {$wordCount} THEN 700 + ({$wordMatchSum} * 10)

                        -- Mayoría de palabras presentes (75%+)
                        WHEN ({$wordMatchSum}) >= ({$wordCount} * 0.75) THEN 600 + ({$wordMatchSum} * 10)

                        -- Nombre comienza con búsqueda
                        WHEN personas.nombres LIKE CONCAT({$escapedSearch}, '%') THEN 500

                        -- Apellido paterno comienza con búsqueda
                        WHEN personas.ape_pat LIKE CONCAT({$escapedSearch}, '%') THEN 450

                        -- Apellido materno comienza con búsqueda
                        WHEN personas.ape_mat LIKE CONCAT({$escapedSearch}, '%') THEN 400

                        -- Algunas palabras presentes (50%+)
                        WHEN ({$wordMatchSum}) >= ({$wordCount} * 0.5) THEN 300 + ({$wordMatchSum} * 10)

                        -- Coincidencia fonética en nombres
                        WHEN SOUNDEX(personas.nombres) = SOUNDEX({$escapedSearch}) THEN 200

                        -- Coincidencia fonética en apellidos
                        WHEN SOUNDEX(personas.ape_pat) = SOUNDEX({$escapedSearch}) THEN 150
                        WHEN SOUNDEX(personas.ape_mat) = SOUNDEX({$escapedSearch}) THEN 140

                        -- Coincidencia parcial mínima
                        ELSE 100 + ({$wordMatchSum} * 5)
                    END) as relevance_score
                "));

                // Ordenar primero por relevancia, luego por el sort seleccionado
                if (!in_array($this->sort, ['documento', 'nombres', 'sucursal'])) {
                    $query->orderBy('relevance_score', 'desc');
                }
            })
            ->when($this->estadoPrestamo === 'Sin préstamos', function ($query) {
                $query->where(function ($subQuery) {
                    // Personas que no son clientes
                    $subQuery->whereNull('clientes.id')
                        // O clientes sin préstamos
                        ->orWhereNotExists(function ($existsQuery) {
                            $existsQuery->select(\DB::raw(1))
                                ->from('prestamos')
                                ->whereColumn('prestamos.cliente_id', 'clientes.id');
                        });
                });
            })
            // FILTRO POR CARTERA: Solo para roles Asesor, Analista y JCC
            // Mostrar clientes que tienen préstamos en la cartera del usuario (SIN importar estado)
            // NOTA: No se filtra por created_by, todos pueden ver todos los clientes
            ->when($this->debeAplicarFiltroCartera(), function ($query) {
                $userId = auth()->id();

                // Mostrar clientes que tienen préstamos en CUALQUIERA de las carteras del usuario
                $query->whereExists(function ($existsQuery) use ($userId) {
                    $existsQuery->select(\DB::raw(1))
                        ->from('prestamos')
                        ->whereColumn('prestamos.cliente_id', 'clientes.id')
                        ->where(function ($carteraQuery) use ($userId) {
                            // Usar OR para buscar en todas las carteras donde esté asignado
                            if (auth()->user()->hasRole('Asesor')) {
                                $carteraQuery->orWhereExists(function ($subQuery) use ($userId) {
                                    $subQuery->select(\DB::raw(1))
                                        ->from('carteras_asesor')
                                        ->whereColumn('carteras_asesor.prestamo_id', 'prestamos.id')
                                        ->where('carteras_asesor.asesor_id', $userId)
                                        ->where('carteras_asesor.estado', 1);
                                });
                            }

                            if (auth()->user()->hasRole('Analista')) {
                                $carteraQuery->orWhereExists(function ($subQuery) use ($userId) {
                                    $subQuery->select(\DB::raw(1))
                                        ->from('carteras_analista')
                                        ->whereColumn('carteras_analista.prestamo_id', 'prestamos.id')
                                        ->where('carteras_analista.analista_id', $userId)
                                        ->where('carteras_analista.estado', 1);
                                });
                            }

                            if (auth()->user()->hasRole('JCC')) {
                                $carteraQuery->orWhereExists(function ($subQuery) use ($userId) {
                                    $subQuery->select(\DB::raw(1))
                                        ->from('carteras_jcc')
                                        ->whereColumn('carteras_jcc.prestamo_id', 'prestamos.id')
                                        ->where('carteras_jcc.jcc_id', $userId)
                                        ->where('carteras_jcc.estado', 1);
                                });
                            }
                        });
                });
            })
            ->when($this->sort === 'documento', function ($query) {
                $query->orderBy('personas.documento', $this->direction);
            })
            ->when($this->sort === 'nombres', function ($query) {
                $query->orderBy('personas.nombres', $this->direction);
            })
            ->when($this->sort === 'sucursal', function ($query) {
                $query->orderByRaw('(SELECT sucursales.sucursal FROM direcciones 
                      INNER JOIN sucursales ON direcciones.sucursal_id = sucursales.id 
                      WHERE direcciones.persona_id = personas.id 
                      LIMIT 1) ' . $this->direction);
            })
            ->when(! in_array($this->sort, ['documento', 'nombres', 'sucursal']), function ($query) {
                $query->orderBy('personas.id', $this->direction);
            })
            ->paginate(10, ['*'], 'page');

        return view('livewire.clientes.show-clientes', compact('personas'));
    }

    protected $queryString = [
        'search' => ['except' => ''],
        'estadoPrestamo' => ['except' => ''],
    ];

    public function order($sort)
    {
        if ($this->sort == $sort) {
            if ($this->direction == 'desc') {
                $this->direction = 'asc';
            } else {
                $this->direction = 'desc';
            }
        } else {
            $this->sort = $sort;
            $this->direction = 'desc';
        }
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->estadoPrestamo = '';
        $this->resetPage();
    }

    public function filtrarPorEstado($estado)
    {
        $this->estadoPrestamo = $this->estadoPrestamo === $estado ? '' : $estado;
        $this->resetPage();
    }

    /**
     * Determina si se debe aplicar filtro de cartera según el rol del usuario
     * DESHABILITADO: Todos los roles pueden ver todos los clientes
     */
    private function debeAplicarFiltroCartera(): bool
    {
        // Filtro deshabilitado - todos los roles pueden ver todos los clientes
        return false;
    }

    /**
     * Sanitiza el término de búsqueda para mejorar resultados y seguridad
     *
     * @param string $search
     * @return string
     */
    private function sanitizeSearch(string $search): string
    {
        // Eliminar espacios múltiples
        $search = preg_replace('/\s+/', ' ', trim($search));

        // Normalizar caracteres con tilde (á -> a, é -> e, etc.)
        $search = $this->removeAccents($search);

        // Eliminar caracteres especiales que no sean letras, números o espacios
        $search = preg_replace('/[^a-zA-Z0-9\s]/', '', $search);

        return $search;
    }

    /**
     * Remueve acentos y caracteres especiales del español
     *
     * @param string $string
     * @return string
     */
    private function removeAccents(string $string): string
    {
        $unwanted_array = [
            'Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A',
            'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a',
            'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u',
            'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y'
        ];

        return strtr($string, $unwanted_array);
    }
}
