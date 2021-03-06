<?php

class BSApiStatisticsTasks extends BSApiTasksBase {

	protected $aTasks = array(
		'getData'
	);

	protected function getRequiredTaskPermissions() {
		return array(
			'getData' => array( 'read' )
		);
	}

	public function task_getData( $oTaskData, $aParams ) {
		$oResponse = $this->makeStandardReturn();

		$sDiagram	= $oTaskData->diagram;
		$sGrain		= $oTaskData->grain;
		$sFrom		= $oTaskData->from;
		$sMode		= $oTaskData->mode;
		$sTo		= $oTaskData->to;

		$aAvailableDiagrams = Statistics::getAvailableDiagrams();
		$aAllowedDiaKeys = array_keys( $aAvailableDiagrams );

		if( empty( $sDiagram ) ) {
			$oResponse->errors['inputDiagrams'] = wfMessage( 'bs-statistics-err-emptyinput' )->plain();
		}
		if( !in_array( $sDiagram, $aAllowedDiaKeys ) ) {
			$oResponse->errors['inputDiagrams'] = wfMessage( 'bs-statistics-err-unknowndia' )->plain();
		}

		if( !array_key_exists( $sGrain, BsConfig::get( 'MW::Statistics::AvailableGrains' ) ) ) {
			$oResponse->errors['InputDepictionGrain'] = wfMessage( 'bs-statistics-err-unknowngrain' )->plain();
		}

		if( empty( $sFrom ) ) {
			$oResponse->errors['inputFrom'] = wfMessage( 'bs-statistics-err-emptyinput' )->plain();
		}
		if( !$oFrom = DateTime::createFromFormat( 'd.m.Y', $sFrom ) ) {
			$oResponse->errors['inputFrom'] = wfMessage( 'bs-statistics-err-invaliddate' )->plain();
		}


		if( empty( $sTo ) ) {
			$oResponse->errors['inputTo'] = wfMessage( 'bs-statistics-err-emptyinput' )->plain();
		}
		if( !$oTo = DateTime::createFromFormat( 'd.m.Y', $sFrom ) ) {
			$oResponse->errors['inputTo'] = wfMessage( 'bs-statistics-err-invaliddate' )->plain();
		}
		if( $oTo > new DateTime() ) {
			$oResponse->errors['inputTo'] = wfMessage( 'bs-statistics-err-invaliddate' )->plain();
		}

		if( isset($oFrom) && isset($oTo) && $oFrom > $oTo ) {
			$oResponse->errors['inputTo'] = wfMessage( 'bs-statistics-err-invalidtofromrelation' )->plain();
		}

		if( empty( $sMode ) ) {
			$oResponse->errors['rgInputDepictionMode'] = wfMessage( 'bs-statistics-err-emptyinput' )->plain();
		}
		if( !in_array( $sMode, array('absolute', 'aggregated', 'list') ) ) {
			$oResponse->errors['rgInputDepictionMode'] = wfMessage( 'bs-statistics-err-unknownmode' )->plain();
		}
		if( !isset( $oResponse->errors['inputDiagrams'])
			&& $sMode == 'list'
			&& !$aAvailableDiagrams[$sDiagram]->isListable() ) {
			$oResponse->errors['rgInputDepictionMode'] = wfMessage( 'bs-statistics-err-modeunsupported' )->plain();
		}

		if( !empty( $oResponse->errors ) ) {
			return $oResponse;
		}

		$oDiagram = Statistics::getDiagram( $sDiagram );
		$oDiagram->setStartTime( $sFrom );
		$oDiagram->setEndTime( $sTo );
		$oDiagram->setActualGrain( $sGrain );
		$oDiagram->setModLabel( $sGrain );
		$oDiagram->setMode( $sMode );
		//$oDiagram->setMessage( $sMessage );
		//$oDiagram->setFilters( $aDiagFilter );

		switch ( $oDiagram->getActualGrain() ) {
			// Here, only those grains are listed where label code differs from grain code.
			case 'W' : $oDiagram->setModLabel( 'W/y' ); break;
			case 'm' : $oDiagram->setModLabel( 'M y' ); break;
			case 'd' : $oDiagram->setModLabel( 'd.m' ); break;
			//default  : $oDiagram->modLabel = false;
		}

		switch ( $oDiagram->getDataSource() ) {
			case BsDiagram::DATASOURCE_DATABASE :
				global $wgDBtype, $wgDBserver, $wgDBuser, $wgDBpassword, $wgDBname;
				switch( $wgDBtype ) {
					case "postgres" : $oReader = new PostGreSQLDbReader(); break;
					case "oracle"   : $oReader = new OracleDbReader(); break;
					default         : $oReader = new MySQLDbReader();
				}
				//$oReader = $sDbType == 'mysql' ? new MySQLDbReader() : new PostGreSQLDbReader();
				$oReader->host = $wgDBserver;
				$oReader->user = $wgDBuser;
				$oReader->pass = $wgDBpassword;
				$oReader->db   = $wgDBname;
				break;
		}

		$intervals = Interval::getIntervalsFromDiagram( $oDiagram );
		if( count( $intervals ) > BsConfig::get( 'MW::Statistics::MaxNumberOfIntervals' ) ) {
			$oResponse->message = wfMessage( 'bs-statistics-interval-too-big' )->plain();
			return $oResponse;
		}

		global $wgDBtype;
		// Pls. keep the space after user, otherwise, user_groups is also replaced
		$sql = $oDiagram->getSQL();
		if ( $wgDBtype == 'postgres' ) $sql = str_replace( '#__user', '#__mwuser', $sql );
		if ( $wgDBtype == 'postgres' ) $sql = str_replace( '#__mwuser_', '#__user_', $sql );
		global $wgDBprefix;
		$sql = str_replace( "#__", $wgDBprefix, $sql );

		foreach ( $oDiagram->getFilters() as $oFilter ) {
			$oFilter->getValueFromTaskData( $oTaskData );
			$sFilterSql = $oFilter->getSql();
			$sql = str_replace( $oFilter->getSqlKey(), $sFilterSql, $sql );
		}

		$oReader->match = $sql;
		$oDiagram->setData( BsCharting::getDataPerDateInterval( $oReader, $oDiagram->getMode(), $intervals, $oDiagram->isListable() ) );

		if ( $oDiagram->isList() ) {
			//$aResult['data']['list'] = BsCharting::drawTable($oDiagram);
			$oResponse->payload['data']['list'] = BsCharting::prepareList( $oDiagram, $oReader );
			$oResponse->payload['label'] = $oDiagram->getTitle();
			$oResponse->success = true;
			return $oResponse;
		}

		$aData = $oDiagram->getData();
		$i = 0;
		foreach ( $intervals as $interval ) {
			$oResponse->payload['data'][] = array(
				'name' => $interval->getLabel(),
				'hits' => (int)$aData[$i],
			);
			$i ++;
		}

		$aAvalableGrains = BsConfig::get( 'MW::Statistics::AvailableGrains' );
		$sLabelMsgKey = 'bs-statistics-label-time';
		if( isset($aAvalableGrains[$oDiagram->getActualGrain()]) ) {
			$sLabelMsgKey = $aAvalableGrains[$oDiagram->getActualGrain()];
		}

		$oResponse->payload['label'] = wfMessage( $sLabelMsgKey )->plain();

		$oResponse->success = true;
		return $oResponse;

	}
}
