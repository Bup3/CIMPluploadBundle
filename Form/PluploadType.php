<?php
/**
 * CIMPluploadBundle - Provides a plupload upload for Symfony2
 * Copyright (C) 2013-2014 christmann informationstechnik + medien GmbH & Co. KG
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

namespace CIM\PluploadBundle\Form;

use \Symfony\Component\Form\Extension\Core\DataTransformer\ArrayToChoicesTransformer;
use \Symfony\Bridge\Doctrine\Form\DataTransformer\EntitiesToArrayTransformer;
use \Symfony\Bridge\Doctrine\Form\EventListener\MergeCollectionListener;
use \Localdev\FrameworkExtraBundle\Form\InjectionListenerType;
use \Symfony\Bridge\Doctrine\Form\DataTransformer\EntityToIdTransformer;
use \Symfony\Bridge\Doctrine\Form\ChoiceList\EntityChoiceList;
use \Symfony\Component\Form\Extension\Core\DataTransformer\ScalarToChoiceTransformer;
use \Symfony\Component\Form\FormView;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Plupload
 *
 * @author Fabian Martin <fabian.martin@christmann.info>
 */
class PluploadType extends InjectionListenerType
{
	/**
	 * {@inheritDoc}
	 */
	public function buildForm(FormBuilder $builder, array $options)
	{
		if ($options['multiple'])
		{
			$builder
					->addEventSubscriber(new MergeCollectionListener())
					->prependClientTransformer(new EntitiesToArrayTransformer($options['choice_list']));
		}
		else
		{
			$builder->prependClientTransformer(new EntityToIdTransformer($options['choice_list']));
		}

		$builder
				->setAttribute('choice_list', $options['choice_list'])
				->setAttribute('multiple', $options['multiple'])
				->setAttribute('required', $options['required'])
				->setAttribute('filter', $options['filter']);
	}

	/**
	 * {@inheritdoc}
	 */
	public function buildView(FormView $view, FormInterface $form)
	{
		$data = $form->getData();
		$filter = $form->getAttribute('filter');

		$filtered = array();
		foreach ($filter as $key => $item)
		{
			$filtered[] = array(
				'title' => $item,
				'extensions' => $key
			);
		}

		$view
				->set('data', $data)
				->set('multiple', $form->getAttribute('multiple'))
				->set('filter', json_encode($filtered))
		;

		$vars = $view->getVars();
		$this->getListener()->addInstance($vars['id'], array(
															'full_name' => $view->get('full_name'),
															'multiple' => $view->get('multiple'),
															'filter' => $view->get('filter'),
													   ));
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDefaultOptions(array $options)
	{
		$entity = $this->getContainer()->getParameter('cim.plupload.entity');
		$default = array(
			'em' => null,
			'class' => $entity,
			'query_builder' => null,
			'multiple' => false,
			'filter' => array(
				'jpg,png' => 'Bilder'
			)
		);

		$options = array_replace($default, $options);

		if (!isset($options['choice_list']))
		{
			$default['choice_list'] = new EntityChoiceList(
				$this->getEntityManager($options['em']),
				$options['class'],
				null,
				$options['query_builder']
			);
		}

		return $default;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getParent(array $options)
	{
		return 'choice';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName()
	{
		return 'plupload';
	}
}