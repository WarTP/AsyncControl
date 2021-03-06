<?php declare(strict_types = 1);

namespace Pd\AsyncControl\UI;

use Nette\Application\UI\Control;
use Nette\Application\UI\Presenter;
use Nette\Bridges\ApplicationLatte\Template;


/**
 * @method render
 */
trait AsyncControlTrait
{

	/**
	 * @var callable
	 */
	protected $asyncRenderer;

        /**
         * @crossOrigin
         * @return void
         * @throws \Throwable
         */
	public function handleAsyncLoad()
	{
		if ( ! $this instanceof Control || ! $this->hasPresenter() || ! $this->getPresenter()->isAjax()) {
			return;
		}
		ob_start(function () {
		});
		try {
			$this->renderAsync(null, null, $this->getParameters());
		} catch (\Throwable $e) {
			ob_end_clean();
			throw $e;
		} catch (\Exception $e) {
			ob_end_clean();
			throw $e;
		}
		$content = ob_get_clean();
                $presenter = $this->getPresenter();
		$presenter->getPayload()->snippets[$this->getSnippetId('async')] = $content;
		$presenter->sendPayload();
	}


	public function renderAsync(string $linkMessage = NULL, array $linkAttributes = NULL, array $renderParams = [])
	{
		if (
			$this instanceof Control
			&& $this->getPresenter()->getParameter('_escaped_fragment_') === NULL
			&& strpos((string) $this->getPresenter()->getParameter(Presenter::SIGNAL_KEY), sprintf('%s-', $this->getUniqueId())) !== 0
		) {
			$template = $this->createTemplate();
			if ($template instanceof Template) {
				$template->add('link', new AsyncControlLink($linkMessage, $linkAttributes, $renderParams));
			}
			$template->setFile(__DIR__ . '/templates/asyncLoadLink.latte');
			$template->render();
		} elseif (is_callable($this->asyncRenderer)) {
			call_user_func($this->asyncRenderer);
		} else {
			call_user_func_array([$this, 'render'], $renderParams);
		}
	}


	public function setAsyncRenderer(callable $renderer)
	{
		$this->asyncRenderer = $renderer;
	}
}

