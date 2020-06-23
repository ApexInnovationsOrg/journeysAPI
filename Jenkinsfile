#!/usr/bin/env groovy

pipeline {
    agent any
    stages {
		stage('Start'){
            steps{
                rocketSend message: "Build for journeysAPI Started", channel: 'jenkins'
            }
        }
		stage('Prepare environment') {
            steps {
                sh "cp /var/lib/jenkins/scripts/journeyapienv.txt .env"
                sh "ls -lha .env"
            }
        }
        stage('Install package') { 
            steps {
                sh 'composer install' 
                sh 'composer update' 
            }
        }
        stage('Push to staging'){
            steps{
                sh 'rsync -avz --no-perms --no-owner --no-group ./ bitnami@apexwebtest.apexinnovations.com:/apex/htdocs/JourneyAPI'
                sh 'rsync -avz --no-perms --no-owner --no-group ./.env bitnami@apexwebtest.apexinnovations.com:/apex/htdocs/JourneyAPI'
            }
        }
    }
    post {
        success{
            rocketSend message: "Build for journeyAPI great success! ᕙ(▀̿̿Ĺ̯̿̿▀̿ ̿) ᕗ", emoji:':camera_with_flash:', channel: 'jenkins'
        }
        unstable{
            rocketSend message: "Build unstable (∩︵∩)", channel: 'jenkins'
        }
        failure{
            rocketSend message: "JourneyAPI Build Failed ┏༼ ◉ ╭╮ ◉༽┓", emoji:':thumbsdown:', channel: 'jenkins'
        }
    }
}