<div class="modal fade" id="limitModal" tabindex="-1" role="dialog" aria-labelledby="limitModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="limitModalLabel">Editar Limite</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="limit-id">
                <div class="form-group">
                    <label for="resource">Recurso</label>
                    <input type="text" class="form-control" id="resource" required>
                </div>
                <div class="form-group">
                    <label for="limit-value">Valor</label>
                    <input type="text" class="form-control" id="limit-value">
                </div>
                <div class="form-group">
                    <label for="description">Descrição</label>
                    <textarea class="form-control" id="description" rows="3"></textarea>
                </div>
                <div class="form-group form-check">
                    <input type="checkbox" class="form-check-input" id="available">
                    <label class="form-check-label" for="available">Disponível</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="save-limit">Salvar</button>
            </div>
        </div>
    </div>
</div>
