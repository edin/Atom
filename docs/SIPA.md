# SIPA

[Atom Framework](Index.md)

SIPA means **Server-driven Interactive Page Actions**.

It is Atom's page interaction model: the server still renders pages, but the browser enhances links and actions so screens can feel like a small SPA.

## Model

The browser does not own application state. A page action posts back to the current page, the page is rebuilt on the server, and the client updates the DOM from the returned HTML.

The usual flow is:

1. a `GET` request renders a page
2. the page template includes an `atom-state` meta value for `#[State]` properties
3. the user clicks an `atom:action` element or submits an enhanced form
4. Atom restores page state
5. Atom hydrates page inputs
6. Atom invokes the `#[PageAction]` method
7. Atom renders the page again
8. the browser updates the configured update root

The server can keep returning complete HTML. The client decides how to apply it.

## Page Actions

```php
use Atom\Page\PageAction;
use Atom\Page\PageRoute;
use Atom\Page\State;

#[PageRoute("/counter")]
final class CounterPage extends AppPage
{
    #[State]
    public int $count = 0;

    #[PageAction("increment")]
    public function increment(): void
    {
        $this->count++;
    }

    #[PageAction("setStep")]
    public function setStep(int $step): void
    {
        $this->step = $step;
    }
}
```

Template:

```html
<button type="button" atom:action="increment">+1</button>
<button type="button" atom:action="setStep(5)">Step 5</button>
```

Forms use `atom:submit`:

```html
<form method="post" atom:submit="save">
    <input name="title">
    <button type="submit">Save</button>
</form>
```

Submit buttons can override the form action:

```html
<form method="post" atom:submit="save">
    <button type="submit">Save</button>
    <button type="submit" atom:action="cancel">Cancel</button>
</form>
```

Action parameters can come from:

- action arguments, such as `setStep(5)`
- route parameters
- body fields
- query parameters
- explicit hydration attributes like `#[FromBody]`, `#[FromRoute]`, and `#[FromQuery]`

## Enhanced Navigation

Links are enhanced only when `atom:navigate` is present:

```html
<a href="/articles" atom:navigate>Articles</a>
```

Atom fetches the target page and applies the returned HTML through `Atom.update`.

## Update Root

Use `atom:update-root` to keep stable layout chrome out of the update.

```html
<body>
    <header>
        <a href="/" atom:navigate>Home</a>
    </header>

    <main atom:update-root>
        <?= $context->fragment($component->content) ?>
    </main>
</body>
```

If both the current page and the returned page contain `atom:update-root`, only that element is updated.
If either side is missing it, Atom falls back to updating `body`.

## Client Scripts

`atom.js` provides the core runtime:

- `atom:navigate`
- `atom:action`
- `atom:submit`
- `atom:update-root`
- page state submission
- `Atom.update`
- `Atom.setUpdateEngine(...)`

The default update engine is intentionally small. It replaces the update root content and preserves common browser state such as focused input values, selection, and scroll.

For smoother updates, load the optional morphdom adapter:

```html
<script src="/atom/client/resources/atom.js"></script>
<script src="/atom/client/resources/morphdom.js"></script>
<script src="/atom/client/resources/atom-morphdom.js"></script>
```

The adapter plugs into the same facade:

```js
Atom.setUpdateEngine({
    update(current, next) {
        // custom update engine
    }
});
```

## Design Rule

SIPA keeps business behavior on the page class. `atom:*` attributes describe transport and update behavior only.

Good:

```html
<button atom:action="delete(12)">Delete</button>
```

The action name maps to a server method.

Avoid putting business rules into JavaScript directives. If a workflow needs more state or validation, put it on the page and let SIPA rerender.
