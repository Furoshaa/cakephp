<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\I18n\Date;

class SleepRecordsController extends AppController
{
    public function index()
    {
        $user = $this->Authentication->getIdentity();
        $startDate = new Date('monday this week');
        
        // Pour la liste (DESC)
        $sleepRecords = $this->SleepRecords->find()
            ->contain(['Users'])
            ->where(['user_id' => $user->id])
            ->order(['date' => 'DESC'])
            ->all();

        // Pour le graphique (ASC)
        $chartRecords = $this->SleepRecords->find()
            ->where(['user_id' => $user->id])
            ->order(['date' => 'ASC'])
            ->all();

        // Préparer les données pour le graphique
        $chartData = [
            'labels' => [],
            'cycles' => [],
            'energy' => [],
            'hours' => []
        ];

        foreach ($chartRecords as $record) {
            $chartData['labels'][] = $record->date->format('d/m/Y');
            $chartData['cycles'][] = $record->sleep_cycles;
            $chartData['energy'][] = $record->energy_level;
            $chartData['hours'][] = $record->sleep_hours;
        }

        $weekStats = $this->SleepRecords->getWeekStats($user->id, $startDate);
        $globalStats = $this->SleepRecords->getGlobalStats($user->id);
        
        $this->set('sleepRecords', $sleepRecords);
        $this->set(compact('weekStats', 'chartData', 'globalStats'));
    }

    public function add()
    {
        $sleepRecord = $this->SleepRecords->newEmptyEntity();
        
        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $data['user_id'] = $this->Authentication->getIdentity()->id;
            
            $sleepRecord = $this->SleepRecords->patchEntity($sleepRecord, $data);
            
            if ($this->SleepRecords->save($sleepRecord)) {
                $this->Flash->success('Enregistrement sauvegardé.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('Erreur lors de la sauvegarde.');
        }
        
        $this->set(compact('sleepRecord'));
    }

    public function edit($id = null)
    {
        $user = $this->Authentication->getIdentity();
        $sleepRecord = $this->SleepRecords->get($id, [
            'conditions' => ['user_id' => $user->id]
        ]);
        
        if ($this->request->is(['patch', 'post', 'put'])) {
            $sleepRecord = $this->SleepRecords->patchEntity($sleepRecord, $this->request->getData());
            if ($this->SleepRecords->save($sleepRecord)) {
                $this->Flash->success('Modifications sauvegardées.');
                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error('Erreur lors de la sauvegarde.');
        }
        
        $this->set(compact('sleepRecord'));
    }
} 