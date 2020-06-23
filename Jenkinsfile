#!/usr/bin/env groovy

pipeline {
    agent any
    stages {
		stage('Start'){
            steps{
                rocketSend message: "Build for journeysAPI Started", channel: 'jenkins'
            }
        }	
        stage('Install package') { 
            steps {
                sh 'composer install' 
            }
        }
        stage('Push to staging'){
            steps{
                sh 'rsync -avz --no-perms --no-owner --no-group ./ bitnami@apexwebtest.apexinnovations.com:/apex/htdocs/JourneyAPI'
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