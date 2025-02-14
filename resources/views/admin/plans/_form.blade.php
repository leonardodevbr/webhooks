<form action="{{ $action }}" method="POST">
    @csrf
    @if(isset($method))
        @method($method)
    @endif

    <div class="form-group">
        <label for="name">Nome</label>
        <input type="text" name="name" class="form-control" id="name" value="{{ old('name', $plan->name ?? '') }}" required>
    </div>
    <div class="form-group">
        <label for="slug">Slug</label>
        <input type="text" name="slug" class="form-control" id="slug" value="{{ old('slug', $plan->slug ?? '') }}" required>
    </div>
    <div class="form-group">
        <label for="price">Preço</label>
        <input type="number" step="0.01" name="price" class="form-control" id="price" value="{{ old('price', $plan->price ?? '') }}" required>
    </div>
    <div class="form-group">
        <label for="billing_cycle">Ciclo de Pagamento</label>
        <select name="billing_cycle" id="billing_cycle" class="form-control" required>
            <option value="monthly" {{ old('billing_cycle', $plan->billing_cycle ?? '') == 'monthly' ? 'selected' : '' }}>Mensal</option>
            <option value="yearly" {{ old('billing_cycle', $plan->billing_cycle ?? '') == 'yearly' ? 'selected' : '' }}>Anual</option>
        </select>
    </div>
    <div class="mb-4">
        <h5>Limites do Plano</h5>
        <table class="table">
            <thead>
            <tr>
                <th>Recurso</th>
                <th>Valor</th>
                <th>Descrição</th>
                <th>Disponível</th>
                <th>Ações</th>
            </tr>
            </thead>
            <tbody id="limits-table">
            @if(isset($plan) && isset($plan->plan_limits))
                @foreach ($plan->plan_limits as $limit)
                    <tr class="{{ $limit->available ? '' : 'text-muted' }}">
                        <td>{{ ucfirst(str_replace('_', ' ', $limit->resource)) }}</td>
                        <td>{{ $limit->limit_value }}</td>
                        <td>{{ $limit->description }}</td>
                        <td>{{ $limit->available ? 'Sim' : 'Não' }}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary edit-limit"
                                    data-limit='{!! $limit->toJson() !!}'>Editar</button>
                            <button type="button" class="btn btn-sm btn-danger remove-limit" data-id="{{ $limit->id }}">Remover</button>
                        </td>
                    </tr>
                @endforeach
            @endif
            </tbody>
        </table>
        <button type="button" class="btn btn-success" id="add-limit">Adicionar Limite</button>
    </div>

    <div class="text-right">
        <button type="submit" class="btn btn-primary">Salvar Plano</button>
        <a href="{{ route('plans.index') }}" class="btn btn-secondary ml-2">Voltar</a>
    </div>
</form>

@include('admin.plans._limit_modal')
