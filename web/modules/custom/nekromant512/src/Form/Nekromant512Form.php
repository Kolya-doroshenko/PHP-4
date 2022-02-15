<?php

namespace Drupal\nekromant512\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * This class one big kostil.
 */
class Nekromant512Form extends FormBase {

  /**
   * @var string[]
   *   Array of tables headers.
   */
  protected $headers = [
    'Year',
    'Jan', 'Feb', 'Mar', 'Q1',
    'Apr', 'May', 'Jun', 'Q2',
    'Jul', 'Aug', 'Sep', 'Q3',
    'Oct', 'Nov', 'Dec', 'Q4',
    'YTD',
  ];

  /**
   * @var bool
   *   Status validate array.
   */
  protected $startArray = FALSE;

  /**
   * @var bool
   *   Status validate array.
   */
  protected $endArray = FALSE;

  /**
   * @var bool
   *   Status valedate.
   */
  protected $valide = TRUE;

  /**
   * @var string[]
   *   Array of tables rows.
   */
  protected $tableRows;

  /**
   * @var bool[]
   */
  protected $checkArray;
  /**
   * @var string[]
   *   Array of tables kvartal month.
   */
  protected $kvartal = [
    1 => ['Jan', 'Feb', 'Mar'],
    2 => ['Apr', 'May', 'Jun'],
    3 => ['Jul', 'Aug', 'Sep'],
    4 => ['Oct', 'Nov', 'Dec'],
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nekromant512_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $this->tableRows = is_null($this->tableRows) ? [
      1 => 1,
    ] : $this->tableRows;
    $form['tablesContainer'] = [
      '#type' => 'container',
    ];

    $form['tablesContainer']['#attributes']['id'] = 'tablesContainer';
    foreach ($this->tableRows as $table => $rows) {
      $this->buildTable($form, $table);
    }

    $form['#attributes'] = [
      'id' => 'my-form',
    ];

    $form['#tree'] = TRUE;

    $form['actions'] = [];

    $form['actions']['addtable'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add table'),
      '#submit' => ['::addTable'],
      '#name' => 'add-table',
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'my-form',
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'my-form',
      ],
    ];

    $form_state->set('tableRows', $this->tableRows);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $valid = $this->validate($form, $form_state);
    $messenger = \Drupal::messenger();
    $messenger->addMessage($valid ? "Valid" : "Invalid", $valid ? $messenger::TYPE_STATUS : $messenger::TYPE_ERROR);
    if ($valid) {
      $this->mathForm($form, $form_state);
    }
    return $form;
  }

  /**
   * This function build table.
   */
  public function buildTable(array &$form, int $tableNumber) {
    $form['tablesContainer'][$tableNumber] = [
      'tableYear' => [
        '#type' => 'table',
        '#header' => $this->headers,
        '#attributes' => [
          'id' => $tableNumber,
        ],
        '#rows' => [],
      ],
      'actions' => [
        '#type' => 'actions',
        'add_row' => [
          '#type' => 'submit',
          '#data' => $tableNumber,
          '#name' => 'addButton_' . $tableNumber,
          '#value' => $this->t('Add row'),
          '#submit' => ['::addRow'],
          '#ajax' => [
            'callback' => '::ajaxCallback',
            'wrapper' => 'my-form',
          ],
        ],
      ],
    ];

    $this->buildRows($form, $tableNumber);
    $form['#attached']['library'][] = 'nekromant512/style';
    return $form;
  }

  /**
   * This function build rows.
   */
  public function buildRows(&$form, $tableNumber) {
    $year = date('Y');
    for ($i = $this->tableRows[$tableNumber]; $i > 0; $i--) {
      $result = [];
      $result['year'] = [
        '#markup' => $year - $i + 1,
      ];
      for ($j = 1; $j <= 4; $j++) {
        foreach ($this->kvartal[$j] as $month) {
          $result[$month] = [
            '#type' => 'number',
          ];
        }
        $result[$j] = [
          '#prefix' => '<span class="result">',
          '#suffix' => '</span>',
          '#markup' => '',
        ];
      }
      $result['result'] = [
        '#prefix' => '<span class="result">',
        '#suffix' => '</span>',
        '#markup' => '',
      ];
      $form['tablesContainer'][$tableNumber]['tableYear'][$i] = $result;
    }
    return $form;
  }

  /**
   * This function is required for Ajax.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * This function adds a table.
   */
  public function addTable(array &$form, FormStateInterface $form_state) {
    $form_state->set('tableRows', $this->tableRows);
    $this->tableRows[] = 1;
    $form_state->setRebuild();
  }

  /**
   * This function adds a rows.
   */
  public function addRow(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getTriggeringElement()['#data'];
    ++$this->tableRows[$id];
    $form_state->setRebuild();
  }

  /**
   * This function validate form.
   */
  public function validate(array &$form, FormStateInterface $form_state) {
    $this->valide = TRUE;
    $this->checkArray = [];
    $this->checkTable($form, $form_state, 1);
    $this->compareTables();
    return $this->valide;
  }

  /**
   * This function compare tables.
   */
  public function compareTables() {

    while (count($this->checkArray) < count($this->tableRows)) {
      $this->checkArray[] = 0;
    }

    foreach ($this->checkArray as $table) {
      if ($this->checkArray[1] != $table) {
        $this->valide = FALSE;
      }
    }
  }

  /**
   * Validate table.
   */
  public function checkTable(array &$form, FormStateInterface $form_state, int $tableNumber) {
    $this->endArray = FALSE;
    $this->startArray = FALSE;
    if ($tableNumber <= count($this->tableRows)) {
      $rowNumber = 1;
      $this->checkRow($form, $form_state, $tableNumber, $rowNumber);
      $this->checkTable($form, $form_state, $tableNumber + 1);
    }
  }

  /**
   * Validate row.
   */
  public function checkRow(array &$form, FormStateInterface $form_state, int $tableNumber, int $rowNumber) {
    if ($rowNumber <= $this->tableRows[$tableNumber]) {
      $colNumber = 11;
      $this->checkCell($form, $form_state, $tableNumber, $rowNumber, $colNumber);
      $this->checkRow($form, $form_state, $tableNumber, $rowNumber + 1);

    }
  }

  /**
   * Validate cell.
   */
  public function checkCell(array &$form, FormStateInterface $form_state, int $tableNumber, int $rowNumber, int $colNumber) {
    if ($colNumber >= 0) {
      $cell = array_values($form_state->getValue(['tablesContainer', $tableNumber, 'tableYear', $rowNumber]))[$colNumber];
      if ($cell !== "") {
        $this->checkArray[$tableNumber][$rowNumber][$colNumber] = 1;
        if ($this->endArray) {
          $this->valide = FALSE;
        }
        else {
          $this->startArray = TRUE;
        }
      }
      else {
        if ($this->startArray) {
          $this->endArray = TRUE;
        }
      }
      $this->checkCell($form, $form_state, $tableNumber, $rowNumber, $colNumber - 1);
    }
  }

  /**
   * This function calculate form.
   */
  public function mathForm(array &$form, FormStateInterface $form_state) {

    for ($tableNumber = 1; $tableNumber <= count($this->tableRows); $tableNumber++) {
      for ($rowNumber = 1; $rowNumber <= $this->tableRows[$tableNumber]; $rowNumber++) {
        $mounts = $form_state->getValue(['tablesContainer', $tableNumber, 'tableYear', $rowNumber]);

        $q1 = ($mounts[Jan] + $mounts[Feb] + $mounts[Mar] + 1) / 3;
        $q2 = ($mounts[Apr] + $mounts[May] + $mounts[Jun] + 1) / 3;
        $q3 = ($mounts[Jul] + $mounts[Aug] + $mounts[Sep] + 1) / 3;
        $q4 = ($mounts[Oct] + $mounts[Nov] + $mounts[Dec] + 1) / 3;
        $ytd = ($q1 + $q2 + $q3 + $q4 + 1) / 4;

        $form['tablesContainer'][$tableNumber]['tableYear'][$rowNumber][1]['#markup'] = round($q1, 2);
        $form['tablesContainer'][$tableNumber]['tableYear'][$rowNumber][2]['#markup'] = round($q2, 2);
        $form['tablesContainer'][$tableNumber]['tableYear'][$rowNumber][3]['#markup'] = round($q3, 2);
        $form['tablesContainer'][$tableNumber]['tableYear'][$rowNumber][4]['#markup'] = round($q4, 2);

        $form['tablesContainer'][$tableNumber]['tableYear'][$rowNumber]['result']['#markup'] = round($ytd, 2);
      }
    }
    return $form;
  }

}
