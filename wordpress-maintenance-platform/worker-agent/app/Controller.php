<?php
namespace App;


use Symfony\Component\HttpFoundation\JsonResponse;


class Controller
{

	/** @var Kernel */
	protected $app;


	/**
	 * @return Kernel
	 */
	public function getApp()
	{
		return kernel();
	}


	public function hasRequest($key = null)
	{
		$request = $this->getApp()->getRequest();

		if ($request->request->has($key)) {
			return true;
		}
		elseif ($request->files->has($key)) {
			return true;
		}
		elseif ($request->query->has($key)) {
			return true;
		}

		return false;
	}


	public function getRequest($key = null, $default = null)
	{
		if (!is_null($key)) {
			return $this->getApp()->getRequest()->get($key, $default);
		}

		return $this->getApp()->getRequest();
	}


	protected function json($data = null, $status = 200, $headers = array())
	{
		return new JsonResponse($data, $status, $headers);
	}
}