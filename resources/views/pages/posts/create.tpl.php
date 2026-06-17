<div class="row d-flex justify-content-center g-3">
    <div class="col-md-9">
        <h1>CREATE NEW POST</h1>
        <form action="/posts" method="POST">
            <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" required name="title" class="form-control" id="title">
            </div>
            <div class="mb-3">
                <label for="message" class="form-label">Message</label>
                <textarea required name="message" class="form-control" id="message" rows="3"></textarea>
            </div>
            <div class="mb-3 text-center">
                <button class="btn btn-success">SAVE</button>
            </div>
        </form>
    </div>
</div>
