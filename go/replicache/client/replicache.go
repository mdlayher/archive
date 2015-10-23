package replicache

import (
	"errors"
	"net"
	"strconv"
	"strings"
)

// Client represents a client connection a replicache server
type Client struct {
	server string
	socket *net.TCPConn
}

// New establishes a new replicache client connection
func New(server string) (*Client, error) {
	// Resolve TCP address
	addr, err := net.ResolveTCPAddr("tcp", server)
	if err != nil {
		return &Client{}, errors.New("replicache: client could not resolve address: " + server)
	}

	// Establish TCP connection
	socket, err := net.DialTCP("tcp", nil, addr)
	if err != nil {
		return &Client{}, errors.New("replicache: client could not connect to server: " + server)
	}

	// Return new client connection
	return &Client{
		server: server,
		socket: socket,
	}, nil
}

// Close closes the connection to the replicache server
func (c Client) Close() error {
	// Perform close command
	res, err := c.command("CLOSE")
	if err != nil {
		return err
	}

	// Check for BYE
	if res[0:3] != "BYE" {
		return errCheck(res)
	}

	// Close socket
	if err := c.socket.Close(); err != nil {
		return err
	}

	return nil
}

// Delete removes an item with the specified key from a replicache server
func (c Client) Delete(key string) error {
	// Perform delete command
	res, err := c.command("DELETE " + key)
	if err != nil {
		return err
	}

	// Check for OK
	if res[0:2] != "OK" {
		return errCheck(res)
	}

	return nil
}

// Flush removes all items from a replicache server
func (c Client) Flush() error {
	// Perform delete command
	res, err := c.command("FLUSH")
	if err != nil {
		return err
	}

	// Check for OK
	if res[0:2] != "OK" {
		return errCheck(res)
	}

	return nil
}

// Get retrieves an item with the specified key from a replicache server
func (c Client) Get(key string) (string, error) {
	// Perform get command
	res, err := c.command("GET " + key)
	if err != nil {
		return "", err
	}

	// Check for OK
	if res[0:2] != "OK" {
		return "", errCheck(res)
	}

	// Check for empty response
	if res == "OK" {
		return "", nil
	}

	// Return the item after OK
	return res[3:len(res)], nil
}

// Server returns the server which this client is connected to
func (c Client) Server() string {
	return c.server
}

// Set sets an item with the specified key into a replicache server
func (c Client) Set(key string, value interface{}) error {
	// Check type of value
	var strValue string
	switch value.(type) {
	case int:
		strValue = strconv.Itoa(value.(int))
	case string:
		strValue = value.(string)
	default:
		return errors.New("replicache: unsupported value type")
	}

	// Perform set command
	res, err := c.command("SET " + key + " " + strValue)
	if err != nil {
		return err
	}

	// Check for OK
	if res != "OK" {
		return errCheck(res)
	}

	return nil
}

// command sends a command and receives a response from the server
func (c Client) command(cmd string) (string, error) {
	// Send command
	if _, err := c.send(cmd); err != nil {
		return "", err
	}

	// Receive response
	res, err := c.receive()
	if err != nil {
		return "", err
	}

	return string(res), nil
}

// receive receives a response from the server
func (c Client) receive() (string, error) {
	// Check for established connection
	if c.socket == nil {
		return "", errors.New("replicache: client is connected to a server")
	}

	// Read server response
	var out string
	for {
		// Check for response
		buf := make([]byte, 2048)
		n, err := c.socket.Read(buf)
		if err != nil {
			return "", err
		}

		// Check for populated buffer
		if n > 1 {
			// Trim unneeded characters
			out = strings.Trim(string(buf[:n]), "\r\n")
			break
		}
	}

	// Return trimmed buffer
	return out, nil
}

// send sends a command to the server
func (c Client) send(cmd string) (int, error) {
	// Check for established connection
	if c.socket == nil {
		return 0, errors.New("replicache: client is connected to a server")
	}

	// Write command to socket
	return c.socket.Write([]byte(cmd + "\r\n"))
}

// errCheck checks a response message for an error, and returns that error
func errCheck(res string) error {
	// Check for client error
	if res[0:5] == "ERROR" {
		return errors.New("replicache: client error: " + res[6:len(res)])
	}

	// Check for server error
	if res[0:12] == "SERVER_ERROR" {
		return errors.New("replicache: server error: " + res[13:len(res)])
	}

	// Undefined error
	return errors.New("replicache: unknown error: " + res)
}
