<?php namespace local; defined('CONFPATH') or die('No direct script access.');

class CDRSMG extends CDRConverter_CDR
{
	// protected $delim_nums = '&';

	protected $conv_func  = array(
		// 'substitution',
		'port2str',
		// 'dn_numbers',
		'num2e164',
		// 'combination_nums',
	);

	protected $aliases = array(
		// Operators
		1  => 's01', // oper Orange
		2  => 's02', // oper Beeline
		3  => 's03', // oper MTS
		4  => 's04', // roil RTU
		5  => 's05', // roil Unitel
		// Contracts
		10 => 'r10', // roil RN-Inform
		11 => 'r11', // roil RSS
	);
	protected $term_ip = array(
		// Operators
		'Orange'  => 1,
		'Beeline' => 2,
		'BeeLine' => 2,
		'Билайн'  => 2,
		'MTC'     => 3, // Латиницей?!?!?!
		'МТС'     => 3, // Кириллицей
		'MTS'     => 3,
		'RTU'     => 4,
		'РТУ'     => 4,
		'Unitel'  => 5,
		// Contracts
		'RN'        => 10,
		'RN-Inform' => 10,
		'РН-Информ' => 10,
		'RCN'       => 11,
		'PCC'       => 11,
	);




	protected function port2str()
	{
		foreach (array('port_from', 'port_to') AS $port_type)
		{
			$port = $this->get($port_type);
			$this->set($port_type, $this->_port2str($port));
		}
	}

	protected function _port2str($port)
	{
		if (isset($this->term_ip[$port]))
			return $this->aliases[$this->term_ip[$port]];
		return 's__';
	}

}
/*
	protected function dn_numbers()
	{
		$a_num = $this->get('A');
		if (preg_match("/^(49[589]\d{7})(\d+)$/", $a_num, $mch))
		{
			$this->set('A', $mch[1]);
			$this->val_ext['num_dn'] = $mch[2];
		}
	}

	protected function combination_nums()
	{
		$a_num = array($this->get('port_from'), $this->get('A'));
		if ( ! empty($this->val_ext['num_dn']))
			$a_num[] = $this->val_ext['num_dn'];
		$b_num = array($this->get('port_to'), $this->get('B'));
		$this->set('A', implode($this->delim_nums, $a_num));
		$this->set('B', implode($this->delim_nums, $b_num));
	}

	protected function substitution()
	{
		// Подмена А номера
		$a_num = $this->get('A');
		if (empty($a_num))
			$this->set('A', 0);
		// ЧР-Информ, ДальСатКом и ГПНШ
		if (preg_match("/^(?>11|15|19)\d{4}/", $a_num))
			$this->set('A', ( ! empty($mch[11])) ? $mch[11] : $a_num);
		// Сайт Системс
		$this->set('A', preg_replace("/^86999(\d+)/", "$1", $a_num));
	}

*/