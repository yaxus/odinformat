<?php namespace local; defined('CONFPATH') or die('No direct script access.');

class CDRRTU extends CDRConverter_CDR
{
	protected $delim_nums = '&';

	protected $conv_func  = array(
		'substitution',
		'port2str',
		// 'dn_numbers',
		'num2alias',
		'redirected_nums',  // если есть доп. поля с переадресующим номером, подставляем их
		'trim_B_88_89',     // обрезаем все номера до 11 символов
		'num2e164',
		'combination_nums', // запись номера с префиксом оператора с которого пришев вызов
	);

	protected $aliases = array(
		// Operators
		1  => 'r01', // SMG (from 15.04.2015) // Unitel (Beeline, MTS)
		2  => 'r02', // MTT
		3  => 'r03', // Macomnet
		// Contracts
		10 => 'r10', // Morgan
		11 => 'r11', // ДальСатКом
		12 => 'r12', // ГрозНефтеГаз
		13 => 'r13', // Автоснабженец
		14 => 'r14', // Нефтегарант
	);
	// Номера сравниваются с полем "Исходящий А-номер" (не для биллинга)
	// Сделано для номеров, к которым добавлен внутренний номер
	protected $a_num2alias = array(
		'74957300242' => 13,
		'74957774547' => 13,
	);
	protected $term_ip = array(
		// Operators
		'10.23.0.235'    => 1, // AS5350XM
		'80.75.130.132'  => 2, // calls not found 2014-11-18
		'80.75.130.143'  => 2, // calls started from 2014-11-24
		'80.75.130.154'  => 2, // use
		'192.168.118.16' => 1, // use from 2015-01-15
		'192.168.118.17' => 1, // not use from 2015-01-14 (switch to SMG 2016)
		'195.128.80.164' => 3, // use
		// Contracts
		'10.2.3.85'      => 10, // Morgan (not used 2015-02-28)
		'10.253.41.4'    => 12, // ГрозНефтеГаз
		'10.254.202.4'   => 12, // ГрозНефтеГаз
		'172.31.255.85'  => 11, // ДальСатКом
		'178.57.197.222' => 11, // ДальСатКом-Интернет
		'185.51.20.85'   => 10, // Morgan
		'185.51.20.86'   => 10, // Morgan
		'185.51.20.87'   => 10, // Morgan
		'185.51.20.88'   => 10, // Morgan
		'185.51.20.89'   => 10, // Morgan
		'185.51.20.90'   => 10, // Morgan
		'212.44.144.69'  => 10, // Morgan NAT (not used 2015-02-28)
		'217.22.160.40'  => 14, // NefteGarant
	);

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
		return 'r__';
	}

	protected function dn_numbers()
	{
		$a_num = $this->get('A');
		if (preg_match("/^(49[589]\d{7})(\d+)$/", $a_num, $mch))
		{
			$this->set('A', $mch[1]);
			$this->val_ext['num_dn'] = $mch[2];
		}
	}

	protected function num2alias()
	{
		$a_num_ext = $this->_num2e164($this->raw_arr[11]); // Исходящий А-номер
		if (isset($this->a_num2alias[$a_num_ext]))
		{
			$pref_id = $this->a_num2alias[$a_num_ext];
			$this->set('port_from', $this->aliases[$pref_id]);
			
			// $a_num = $this->get('A');
			$this->set('A', '7'.$this->get('A'));
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

	protected function redirected_nums()
	{
		if (empty($this->raw_arr[14]))
			return TRUE;
		// Исходящий переадресующий номер
		$redir_num = ( ! empty($this->raw_arr[14])) ? $this->raw_arr[14] : ''; 
		if (strlen($redir_num) != 10)
		{
			Log::instance()->error("Redirected number: {$redir_num} must be 10 characters. File string number #".$this->file_num_str());
			return FALSE;
		}
		$this->set('A', $redir_num);
	}

	protected function trim_B_88_89()
	{
		$a_num = $this->get('B');
		// echo $a_num,' - ',substr($a_num, 0, 2),' - ',strlen($a_num); exit;
		if (strlen($a_num) > 11 AND (substr($a_num, 0, 2) == '88' OR substr($a_num, 0, 2) == '89'))
			// {echo $a_num; exit;}
			$this->set('B', substr($a_num, 0, 11));
	}
}

/*
'u00' => array( // unitel_bsh
	's' => 600,
	'f' => 631,
),
'u06' => array( // unitel_mts
	's' => 792,
	'f' => 855,
),
'u10' => array( //unitel_ekvant
	's' => 10600,
	'f' => 10695,
),
'u20' => array( // unitel_roil_smg
	's' => 20600,
	'f' => 20727,
),
'u30' => array( // unitel_beeline
	's' => 30600,
	'f' => 30695,
),
'u40' => array( // unitel_roil_rn
	's' => 40600,
	'f' => 40855,
),
'u50' => array( // unitel_roilcom
	's' => 50600,
	'f' => 50855,
),
*/
