<div class="form-group row">
    <div class="col">
        <textarea id="comment" name="comment" class="form-control{{ $errors->has('comment') ? ' is-invalid' : '' }}" rows="10" required>{{ old('comment', $torrentComment->comment) }}</textarea>

        @if ($errors->has('comment'))
            <div class="invalid-feedback">
                <strong>{{ $errors->first('comment') }}</strong>
            </div>
        @endif
    </div>
</div>

<button type="submit" class="btn btn-primary">{{ trans('messages.common.submit') }}</button>
