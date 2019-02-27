<div class="form-group row">
    <div class="col">
        <textarea id="message" name="message" class="form-control{{ $errors->has('message') ? ' is-invalid' : '' }}" rows="10" required>{{ old('message', $threadMessage->message) }}</textarea>

        @if ($errors->has('message'))
            <div class="invalid-feedback">
                <strong>{{ $errors->first('message') }}</strong>
            </div>
        @endif
    </div>
</div>

<button type="submit" class="btn btn-primary">{{ trans('messages.common.submit') }}</button>
