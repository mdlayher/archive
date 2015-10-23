# Monty Hall Paradox - CS5300 Final Project - Andy Ladd, Matt Layher

# Import our specific environment and task
from montyhallenv import MontyHallEnv
from montyhalltask import MontyHallTask

# Import PyBrain reinforcement learning
from pybrain.rl.agents import LearningAgent
from pybrain.rl.learners.valuebased import ActionValueTable
from pybrain.rl.learners import Q
from pybrain.rl.experiments import Experiment
from pybrain.rl.explorers import EpsilonGreedyExplorer

import random

# global - TimesWon
TimesWon = 0

#global - TimesLost
TimesLost = 0

# Main program
def main():
	# print title
	print "Monty Hall Paradox - CS5300 Final Project - Andy Ladd, Matt Layher"
	print "- - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -"

	# Number of iterations to perform
	iterations = 100000

	# Define action value table (3 states, 2 actions)
	table = ActionValueTable(3, 2)
	table.initialize()

	# Define learning agent
	learner = Q(0.5, 0.0)
	learner._setExplorer(EpsilonGreedyExplorer(0,0))
	agent = LearningAgent(table, learner)

	# Define environment
	environment = MontyHallEnv()

	# Define task on environment
	task = MontyHallTask(environment)

	# Run it!
	print "[main] running {0} iterations using PyBrain...".format(iterations)
	for i in range(0, iterations):
		RunMonty(environment, task, agent)
	print "[main] iterations complete!"

	# Print results
	print "[main] results:"
	print "\twins:   ", TimesWon
	print "\tlosses: ", TimesLost

def RunMonty(environment, task, agent):
	global TimesWon
	global TimesLost
	global TotalRuns

	# winner and users choice should already be selected by now from the environment.reset() method
	# so, reveal a FOR SURE loser
	environment.findLoser(environment.getUsersChoice())

	# attempts to check if the user should switch or not
	agent.integrateObservation(environment.getUsersChoice())
	task.performAction(agent.getAction())

	# reward of 1 means they won, -1 means they lost
	reward = task.getReward(environment.getUsersChoice())

	# reward flag of 0 means the user switched choices
	agent.giveReward(reward)
	agent.learn()

	# flag a win or loss
	if (reward == 1):
		TimesWon += 1
	else:
		TimesLost += 1

	# exit the loop
	exitBool = False

	# reset the game and try again
	agent.reset()
	environment.reset()

# run it!
main()
